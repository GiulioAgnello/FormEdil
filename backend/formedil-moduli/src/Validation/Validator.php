<?php

declare(strict_types=1);

namespace Formedil\Moduli\Validation;

use Formedil\Moduli\Schema\SchemaProvider;

/**
 * Validazione di una richiesta contro lo schema canonico.
 *
 * Scorre gli step/campi previsti per la variante, rispettando le condizioni,
 * e verifica obbligatorietà e formati (CF, P.IVA, email) e i vincoli min/max
 * di gruppi e tabelle. Restituisce un elenco di errori per campo.
 */
final class Validator
{
    /** @var array<string,string> name => messaggio */
    private array $errors = [];

    /**
     * @param array<string,mixed> $dati
     * @return array<string,string> errori (vuoto = valido)
     */
    public function validate(string $variante, array $dati): array
    {
        $this->errors = [];
        $schema = SchemaProvider::get();

        if (!SchemaProvider::isValidVariant($variante)) {
            $this->errors['variante'] = 'Variante non valida.';
            return $this->errors;
        }

        foreach (($schema['steps'] ?? []) as $step) {
            if (!$this->appliesToVariant($step['variants'] ?? null, $variante)) {
                continue;
            }
            foreach (($step['fields'] ?? []) as $field) {
                $this->validateField($field, $variante, $dati);
            }
        }

        return $this->errors;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @param array<string,mixed> $field
     * @param array<string,mixed> $dati
     */
    private function validateField(array $field, string $variante, array $dati): void
    {
        if (!$this->appliesToVariant($field['variants'] ?? null, $variante)) {
            return;
        }
        if (!$this->conditionMet($field['condition'] ?? null, $dati)) {
            return;
        }

        $name = (string) ($field['name'] ?? '');
        $type = (string) ($field['type'] ?? 'text');
        $required = (bool) ($field['required'] ?? false);
        $value = $dati[$name] ?? null;

        switch ($type) {
            case 'checkboxGroup':
                $count = is_array($value) ? count($value) : 0;
                $min = (int) ($field['min'] ?? ($required ? 1 : 0));
                if ($count < $min) {
                    $this->errors[$name] = 'Selezionare almeno ' . max(1, $min) . ' opzione/i.';
                }
                break;

            case 'acknowledgment':
                if ($required && $value !== true) {
                    $this->errors[$name] = 'È necessario accettare questa dichiarazione.';
                }
                break;

            case 'repeater':
            case 'partecipantiTable':
            case 'docentiTable':
                $this->validateCollection($field, is_array($value) ? $value : []);
                break;

            case 'provinciaComuneCap':
                $this->validateLuogo($name, is_array($value) ? $value : [], $required);
                break;

            default:
                $this->validateScalar($field, $value, $required);
                break;
        }
    }

    /**
     * @param array<string,mixed> $field
     * @param mixed $value
     */
    private function validateScalar(array $field, $value, bool $required): void
    {
        $name = (string) $field['name'];
        $str = is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : '');

        if ($str === '') {
            if ($required) {
                $this->errors[$name] = 'Campo obbligatorio.';
            }
            return;
        }

        $err = $this->checkFormat($field['validation'] ?? null, $str);
        if ($err !== null) {
            $this->errors[$name] = $err;
        }
    }

    /**
     * Valida un campo Provincia/Comune/CAP.
     *
     * @param array<string,mixed> $value
     */
    private function validateLuogo(string $name, array $value, bool $required): void
    {
        $provincia = trim((string) ($value['provincia'] ?? ''));
        $comune = trim((string) ($value['comune'] ?? ''));
        $cap = trim((string) ($value['cap'] ?? ''));

        if ($provincia === '' && $comune === '' && $cap === '') {
            if ($required) {
                $this->errors[$name] = 'Campo obbligatorio.';
            }
            return;
        }

        if ($provincia === '' || $comune === '') {
            $this->errors[$name] = 'Selezionare provincia e comune.';
            return;
        }
        if ($cap !== '' && !preg_match('/^[0-9]{5}$/', $cap)) {
            $this->errors[$name] = 'CAP non valido.';
            return;
        }
        if ($required && $cap === '') {
            $this->errors[$name] = 'Indicare il CAP.';
        }
    }

    /**
     * Valida una collezione (giornate / partecipanti / docenti).
     *
     * @param array<string,mixed> $field
     * @param array<int,mixed> $items
     */
    private function validateCollection(array $field, array $items): void
    {
        $name = (string) $field['name'];
        $min = (int) ($field['min'] ?? 1);
        $max = isset($field['max']) ? (int) $field['max'] : null;
        $count = count($items);

        if ($count < $min) {
            $this->errors[$name] = 'Inserire almeno ' . $min . ' voce/i.';
            return;
        }
        if ($max !== null && $count > $max) {
            $this->errors[$name] = 'Massimo ' . $max . ' voci consentite.';
            return;
        }

        foreach ($items as $i => $item) {
            if (!is_array($item)) {
                $this->errors["{$name}.{$i}"] = 'Voce non valida.';
                continue;
            }
            foreach (($field['itemFields'] ?? []) as $sub) {
                $subName = (string) $sub['name'];
                $subReq = (bool) ($sub['required'] ?? false);
                $val = $item[$subName] ?? null;
                $str = is_string($val) ? trim($val) : (is_scalar($val) ? (string) $val : '');

                if ($str === '') {
                    if ($subReq) {
                        $this->errors["{$name}.{$i}.{$subName}"] = 'Campo obbligatorio.';
                    }
                    continue;
                }
                $err = $this->checkFormat($sub['validation'] ?? null, $str);
                if ($err !== null) {
                    $this->errors["{$name}.{$i}.{$subName}"] = $err;
                }
            }
        }
    }

    private function checkFormat(?string $rule, string $value): ?string
    {
        switch ($rule) {
            case 'cf':
                return CodiceFiscale::isValid($value) ? null : 'Codice Fiscale non valido.';
            case 'piva':
                return self::isValidPiva($value) ? null : 'Partita IVA non valida.';
            case 'email':
                return is_email($value) ? null : 'Email non valida.';
            default:
                return null;
        }
    }

    /** Validazione P.IVA italiana: 11 cifre con checksum (algoritmo Luhn pari/dispari). */
    public static function isValidPiva(string $piva): bool
    {
        $piva = trim($piva);
        if (!preg_match('/^[0-9]{11}$/', $piva)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $n = (int) $piva[$i];
            if ($i % 2 === 1) { // posizioni pari (1-based): raddoppia
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
        }
        return $sum % 10 === 0;
    }

    /**
     * @param string[]|null $variants
     */
    private function appliesToVariant(?array $variants, string $variante): bool
    {
        return $variants === null || in_array($variante, $variants, true);
    }

    /**
     * @param array<string,mixed>|null $condition
     * @param array<string,mixed> $dati
     */
    private function conditionMet(?array $condition, array $dati): bool
    {
        if ($condition === null) {
            return true;
        }
        $field = (string) ($condition['field'] ?? '');
        $current = $dati[$field] ?? null;

        if (array_key_exists('includes', $condition)) {
            return is_array($current) && in_array($condition['includes'], $current, true);
        }
        if (array_key_exists('equals', $condition)) {
            return $current === $condition['equals'];
        }
        return true;
    }
}
