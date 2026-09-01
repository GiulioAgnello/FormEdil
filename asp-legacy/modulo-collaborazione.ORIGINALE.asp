<%
'nomenu = 1
id_url = 31
%>
<!--#include virtual="inc_top.asp"-->
<%
if request.form("ins") = "1" then
	
	nome = repl(request.form("nome"))
	cognome = repl(request.form("cognome"))
	rag_soc = repl(request.form("rag_soc"))
	indirizzo = repl(request.form("indirizzo"))
	provincia = repl(request.form("provincia"))
	
	comune = repl(request.form("comune"))
	temp = split(comune,"_")
	id_comune = temp(ubound(temp))
	comune = replace(comune,"_"&id_comune,"")
	
	cap = repl(request.form("cap"))
	piva = repl(request.form("piva"))
	telefono = repl(request.form("telefono"))
	email = repl(request.form("email"))
	pec = repl(request.form("pec"))
	
	doc = replace(repl(request.form("doc"))," ","%20")
	
	tipo_att = repl(request.form("tipo_att"))
	iscrizione = request.form("iscrizione")
	ateco = repl(request.form("ateco"))
	numero_ce = repl(request.form("numero_ce"))
	numero_ec = repl(request.form("numero_ec"))
	ce = repl(request.form("ce"))
	ec = repl(request.form("ec"))	
	corso_normativa = repl(request.form("corso_normativa"))
	corso = request.form("corso")
	if request.form("dal") <> "" then dal = year(request.form("dal"))&"-"&month(request.form("dal"))&"-"&day(request.form("dal"))
	if request.form("al") <> "" then al = year(request.form("al"))&"-"&month(request.form("al"))&"-"&day(request.form("al"))
	sogg_rag_soc = repl(request.form("sogg_rag_soc"))
	sogg_indirizzo = repl(request.form("sogg_indirizzo"))
	sogg_tel = repl(request.form("sogg_tel"))
	sogg_email = repl(request.form("sogg_email"))
	sogg_pec = repl(request.form("sogg_pec"))
	resp_rag_soc = repl(request.form("resp_rag_soc"))
	resp_indirizzo = repl(request.form("resp_indirizzo"))
	resp_tel = repl(request.form("resp_tel"))
	resp_email = repl(request.form("resp_email"))
	prof_nome = repl(request.form("prof_nome"))
	prof_cognome = repl(request.form("prof_cognome"))
	prof_cf = repl(request.form("prof_cf"))
	prof_luogo_nascita = repl(request.form("prof_luogo_nascita"))
	if request.form("prof_data_nascita") <> "" then prof_data_nascita = year(request.form("prof_data_nascita"))&"-"&month(request.form("prof_data_nascita"))&"-"&day(request.form("prof_data_nascita"))
	prof_residenza = repl(request.form("prof_residenza"))
	prof_tel = repl(request.form("prof_tel"))
	prof_email = repl(request.form("prof_email"))
	
	modulo_b = replace(repl(request.form("modulo_b"))," ","%20")
	
	sede_indirizzo = repl(request.form("sede_indirizzo"))
	sede_provincia = repl(request.form("sede_provincia"))
	
	sede_comune = repl(request.form("sede_comune"))
	if sede_comune <> "" then 
		temp = split(sede_comune,"_")
		id_sede_comune = temp(ubound(temp))
		sede_comune = replace(sede_comune,"_"&id_sede_comune,"")	
	end if
	
	corso_altro = repl(request.form("corso_altro"))
	
	sede_cap = repl(request.form("sede_cap"))
	progetto = repl(request.form("progetto"))
	note = repl(request.form("note"))
	
	Set MM_editCmd = Server.CreateObject("ADODB.Command")
	MM_editCmd.ActiveConnection = MM_edile_STRING	
	
	if request.form("id") = "" then
	
		trattamento = request.form("trattamento")
		if trattamento = "0" then
		%>
		<script type="text/javascript">
		alert('Devi autorizzare il trattamento dei dati.\n\n<%=replace(ucase(edile_nome),"'","")%>');
		history.back();
		</script>
		<%
		end if
		%>
		<!--#include virtual="captcha-check.asp"-->
		<%
		if captchaG = 0 then
		%>
		<script type="text/javascript">
		alert('Non hai superato il test di Google.\n\n<%=replace(ucase(edile_nome),"'","")%>');
		history.back();
		</script>
		<%	
		response.End()	
		end if

		MM_editCmd.CommandText = "INSERT INTO collaborazioni (nome, cognome, rag_soc, indirizzo, provincia, comune, cap, id_comune, piva, telefono, email, pec, doc, tipo_att, iscrizione, ateco, numero_ce, numero_ec, ce, ec) VALUES ('"&nome&"', '"&cognome&"', '"&rag_soc&"', '"&indirizzo&"', '"&provincia&"', '"&comune&"', '"&cap&"', "&id_comune&", '"&piva&"', '"&telefono&"', '"&email&"', '"&pec&"', '"&doc&"', '"&tipo_att&"', '"&iscrizione&"', '"&ateco&"', '"&numero_ce&"', '"&numero_ec&"', '"&ce&"', '"&ec&"')"
		MM_editCmd.Execute
		
		Set rss = Server.CreateObject("ADODB.Recordset")
		rss.ActiveConnection = MM_edile_STRING
		rss.Source = "SELECT id FROM collaborazioni ORDER BY id DESC LIMIT 0,1"
		rss.CursorType = 0
		rss.CursorLocation = 2
		rss.LockType = 1
		rss.Open()
		if not rss.eof then id = cstr(rss("id"))
		rss.ActiveConnection.close		

	else 'SALVATO CON ID
	
		id = request.form("id")
		
		if request.form("wizard") = "1" then

			MM_editCmd.CommandText = "UPDATE collaborazioni SET nome = '"&nome&"', cognome = '"&cognome&"', rag_soc = '"&rag_soc&"', indirizzo = '"&indirizzo&"', provincia = '"&provincia&"', comune = '"&comune&"', cap = '"&cap&"', id_comune = "&id_comune&", piva = '"&piva&"', telefono = '"&telefono&"', email = '"&email&"', pec = '"&pec&"', doc = '"&doc&"', tipo_att = '"&tipo_att&"', iscrizione = '"&iscrizione&"', ateco = '"&ateco&"', numero_ce = '"&numero_ce&"', numero_ec = '"&numero_ec&"', ce = '"&ce&"', ec = '"&ec&"' WHERE id = "&id
			MM_editCmd.Execute
		
		elseif request.form("wizard") = "2" then
		
			MM_editCmd.CommandText = "UPDATE collaborazioni SET corso_normativa = '"&corso_normativa&"', dal = '"&dal&"', al = '"&al&"', sogg_rag_soc = '"&sogg_rag_soc&"', sogg_indirizzo = '"&sogg_indirizzo&"', sogg_tel = '"&sogg_tel&"', sogg_email = '"&sogg_email&"', sogg_pec = '"&sogg_pec&"', resp_rag_soc = '"&resp_rag_soc&"', resp_indirizzo = '"&resp_indirizzo&"', resp_tel = '"&resp_tel&"', resp_email = '"&resp_email&"' WHERE id = "&id
			MM_editCmd.Execute
			
		elseif request.form("wizard") = "3" then
			
			MM_editCmd.CommandText = "UPDATE collaborazioni SET corso = '"&corso&"', prof_nome = '"&prof_nome&"', prof_cognome = '"&prof_cognome&"', prof_luogo_nascita = '"&prof_luogo_nascita&"', prof_data_nascita = '"&prof_data_nascita&"', prof_residenza = '"&prof_residenza&"', prof_tel = '"&prof_tel&"', prof_email = '"&prof_email&"', modulo_b = '"&modulo_b&"', sede_indirizzo = '"&sede_indirizzo&"', sede_provincia = '"&sede_provincia&"', sede_comune = '"&sede_comune&"', sede_cap = '"&sede_cap&"', id_sede_comune = "&id_sede_comune&", progetto = '"&progetto&"', corso_altro = '"&corso_altro&"' WHERE id = "&id
			MM_editCmd.Execute

			MM_editCmd.CommandText = "DELETE FROM collaborazioni_lav WHERE id_collaborazione = "&id
			MM_editCmd.Execute		
			
			MM_editCmd.CommandText = "DELETE FROM collaborazioni_giorni WHERE id_collaborazione = "&id
			MM_editCmd.Execute					
			
			for i = 1 to 30
				execute("nome"&cstr(i)&" = repl(request.Form("&chr(34)&"nome"&cstr(i)&chr(34)&"))")
				execute("cognome"&cstr(i)&" = repl(request.Form("&chr(34)&"cognome"&cstr(i)&chr(34)&"))")
				execute("data"&cstr(i)&" = repl(request.Form("&chr(34)&"data"&cstr(i)&chr(34)&"))")
				
				if eval("nome"&cstr(i)) <> "" then
					data_nascita = year(eval("data"&cstr(i)))&"-"&month(eval("data"&cstr(i)))&"-"&day(eval("data"&cstr(i)))

					MM_editCmd.CommandText = "INSERT INTO collaborazioni_lav (id_collaborazione, nome, cognome, data_nascita) VALUES ("&id&", '"&eval("nome"&cstr(i))&"', '"&eval("cognome"&cstr(i))&"', '"&data_nascita&"')"
					MM_editCmd.Execute
		
				end if
				
				execute("giorno"&cstr(i)&" = repl(request.Form("&chr(34)&"giorno"&cstr(i)&chr(34)&"))")
				execute("da"&cstr(i)&" = repl(request.Form("&chr(34)&"da"&cstr(i)&chr(34)&"))")
				execute("a"&cstr(i)&" = repl(request.Form("&chr(34)&"a"&cstr(i)&chr(34)&"))")
				
				if eval("giorno"&cstr(i)) <> "" then
					giorno = year(eval("giorno"&cstr(i)))&"-"&month(eval("giorno"&cstr(i)))&"-"&day(eval("giorno"&cstr(i)))

					MM_editCmd.CommandText = "INSERT INTO collaborazioni_giorni (id_collaborazione, da, a, giorno) VALUES ("&id&", '"&eval("da"&cstr(i))&"', '"&eval("a"&cstr(i))&"', '"&giorno&"')"
					MM_editCmd.Execute
		
				end if		
			next
		
		elseif request.form("wizard") = "4" then
		
			MM_editCmd.CommandText = "UPDATE collaborazioni SET note = '"&note&"' WHERE id = "&id
			MM_editCmd.Execute
			
			nome_file = "coll-a-b-"&replace(replace(replace(replace(now(),"/","-"),":","-"),".","-")," ","")&".pdf"
			
			if instr(request.ServerVariables("HTTP_HOST"),"formedillecce.it") > 0 then
				
				Set Pdf = Server.CreateObject("Persits.Pdf")
				Set Doc = Pdf.CreateDocument
				url = sito_url&"/modulo-collaborazione-pdf.asp?id="&id
				response.Write(url)
				Doc.ImportFromUrl url,"PageWidth=700,PageHeight=1150,TopMargin=10,BottomMargin=10"
				Filename = Doc.Save( Server.MapPath("/public/moduli/"&nome_file), true ) 
				
			end if
				
			MM_editCmd.CommandText = "UPDATE collaborazioni SET pdf = '"&replace(nome_file,"'","\'")&"' WHERE id = "&id
			MM_editCmd.Execute
			
			if instr(request.ServerVariables("HTTP_HOST"),"formedillecce.it") > 0 then
				
				Set xml = Server.CreateObject("Microsoft.XMLHTTP")
				xml.Open "GET", sito_url&"/template-email.asp", false
				xml.Send
				temp = split(xml.responsetext,"divisore_template")
				
				mail_header = temp(0)
				mail_footer = temp(1)	
				
				corpo = "Gentile <b style=text-transform:capitalize>"&lcase(request.form("nome"))&" "&lcase(request.Form("cognome"))&"</b>, abbiamo ricevuto correttamente la tua richiesta di collaborazione.<br><br>Troverai un riepilogo della tua domanda in <strong>allegato</strong> a questa mail.<br><br>Grazie"
				
				corpo = mail_header & corpo & mail_footer
				
				oggetto = "Richiesta Collaborazione - "&edile_nome	
						
				fileb = request.Form("modulo_b")&""
				if fileb <> "" then
					fileb = Server.MapPath("/public/documenti/"&fileb)
					set fs_fileb=Server.CreateObject("Scripting.FileSystemObject")
					if fs_fileb.FileExists(fileb) = false then
						fileb = ""
					end if
					set fs_fileb=nothing
				end if	
			
				if request.form("email") <> "francesco@650mb.com" then

					Set objCDOSYSMail = Server.CreateObject("CDO.Message")
					With objCDOSYSMail
					Set .Configuration = iConf_noreply
					.From = "no-reply@formedillecce.it" 
					.HTMLBody= corpo
					.To = "responsabileareasicurezza@formedillecce.it; mlongo@650mb.com; direttore@formedillecce.it"
					.Subject = oggetto
					.AddAttachment Server.MapPath("/public/moduli/"&nome_file)
					if fileb <> "" then .AddAttachment fileb 
					.Send 
					End With
					Set objCDOSYSMail = Nothing	
					
				end if
				
				'COPIA ALL'AZIENDA
				if instr(request.form("email"),"@") > 0 then
					Set objCDOSYSMail = Server.CreateObject("CDO.Message")
					With objCDOSYSMail
					Set .Configuration = iConf_noreply
					.From = "no-reply@formedillecce.it" 
					.HTMLBody= corpo
					.To = request.form("email")
					.Subject = oggetto
					.AddAttachment Server.MapPath("/public/moduli/"&nome_file)
					if fileb <> "" then .AddAttachment fileb
					.Send 
					End With
					Set objCDOSYSMail = Nothing	
				end if				
								
			end if
		
		end if

	end if	
	
	MM_editCmd.ActiveConnection.Close		
	
	if request.form("wizard") = "4" then 'FINITO
		response.Redirect(pagina_url&"?msg=ok&email="&request.form("email"))
	else
		wizard = "2"
		if request.form("wizard") <> "" then 
			wizard = cint(request.form("wizard"))
			if instr(request.form("submit"),"Continua") > 0 then
				wizard = wizard + 1
			else
				wizard = wizard - 1
			end if
		end if
		response.Redirect(pagina_url&"?id="&id&"&wizard="&wizard)
	end if
end if
		
if request.QueryString("id") <> "" then
	Set rss = Server.CreateObject("ADODB.Recordset")
	rss.ActiveConnection = MM_edile_STRING
	rss.Source = "SELECT * FROM collaborazioni WHERE id = "&request.QueryString("id")
	rss.CursorType = 0
	rss.CursorLocation = 2
	rss.LockType = 1
	rss.Open()
	if not rss.eof then
	
		if rss("pdf")&"" <> "" then 'controllo se qualcuno accede a vecchie domande
			response.Redirect(pagina_url)
			response.End()
		end if
	
		nome = rss("nome")&""
		cognome = rss("cognome")&""
		rag_soc = rss("rag_soc")&""
		indirizzo = rss("indirizzo")&""
		provincia = rss("provincia")&""
		
		comune = rss("comune")&""		
		cap = rss("cap")&""
		piva = rss("piva")&""
		telefono = rss("telefono")&""
		email = rss("email")&""
		pec = rss("pec")&""
		
		doc = rss("doc")&""
		
		tipo_att = rss("tipo_att")&""
		iscrizione = rss("iscrizione")&""
		ateco = rss("ateco")&""
		numero_ce = rss("numero_ce")&""
		numero_ec = rss("numero_ec")&""
		ce = rss("ce")&""
		ec = rss("ec")&""	
		corso_normativa = rss("corso_normativa")&""
		corso = rss("corso")&""
		
		corso_altro = rss("corso_altro")&""
		
		dal = rss("dal")&""
		al = rss("al")&""
		sogg_rag_soc = rss("sogg_rag_soc")&""
		sogg_indirizzo = rss("sogg_indirizzo")&""
		sogg_tel = rss("sogg_tel")&""
		sogg_email = rss("sogg_email")&""
		sogg_pec = rss("sogg_pec")&""
		resp_rag_soc = rss("resp_rag_soc")&""
		resp_indirizzo = rss("resp_indirizzo")&""
		resp_tel = rss("resp_tel")&""
		resp_email = rss("resp_email")&""
		prof_nome = rss("prof_nome")&""
		prof_cognome = rss("prof_cognome")&""
		prof_cf = rss("prof_cf")&""
		prof_luogo_nascita = rss("prof_luogo_nascita")&""
		prof_data_nascita = rss("prof_data_nascita")&""
		prof_residenza = rss("prof_residenza")&""
		prof_tel = rss("prof_tel")&""
		prof_email = rss("prof_email")&""
		
		modulo_b = rss("modulo_b")&""
		
		sede_indirizzo = rss("sede_indirizzo")&""
		sede_provincia = rss("sede_provincia")&""
		
		sede_comune = rss("sede_comune")&""		
		sede_cap = rss("sede_cap")&""
		progetto = rss("progetto")&""
		note = rss("note")&""	
	end if
	rss.ActiveConnection.close	
end if
%>

<div class="col-md-12 schermata">

<div style="text-align:center">
<h1 style="margin-bottom:0; margin:0px auto;"><%=h1%></h1>
</div>

<% if request.QueryString("msg") = "ok" then %>
	<br>
    Hai inoltrato correttamente la tua <strong>richiesta di collaborazione.</strong><br><br>
    Riceverai in posta elettronica, all'indirizzo <strong><%=request.QueryString("email")%></strong>, copia della richiesta.
    <br><br>
    Verrai ricontattato dal nostro Responsabile Area Sicurezza appena possibile, grazie. 
<% else %>

	<% 
	wizard = request.QueryString("wizard")
	if wizard = "" then 
	%>
        <div class="col-md-12" style="padding:0; margin:0; font-size:14px;">
            <%=corpo%>
        </div>
        
        <div class="col-md-12" align="center">
            <h3 style="color:#222; margin:0;">RICHIESTA DI COLLABORAZIONE</h3>
        </div>
        
        <div class="col-md-12" align="right">
            I campi contrassegnati con * sono OBBLIGATORI
        </div>
	<% end if %>
    
    <div class="col-md-12">   
    
        <link href="/css/progress-bar.css" rel="stylesheet" type="text/css" />         
    
        <ul class="progress-indicator">
            <% for i = 1 to 4 %>
            <li class="<% if cstr(i) = wizard OR (wizard = "" AND i = 1) then 
                    response.Write("active") 
                else
                    if wizard <> "" then
                        if cint(wizard) > i then response.Write("completed")
                    end if
                end if	
                %>">
                <span class="bubble"></span>
                <% if i = 1 then %>
                Dati Anagrafici
                <% elseif i = 2 then  %>
                Indicazioni Corso
                <% elseif i = 3 then  %>
                Dettagli e Partecipanti
                <% elseif i = 4 then  %>
                Concludi
                <% end if %>                                
                
                 <br><small><% if wizard <> "" then
                    if i < cint(wizard) then %>
                    (Completo)<% 
                    end if
                end if %></small>
            </li>
            <% next %>
        </ul>             
        
    </div>

<form action="" method="post" id="formiscrizione" name="formiscrizione">
<input type="hidden" name="ins" value="1">
<input type="hidden" name="id" value="<%=request.QueryString("id")%>">
<input type="hidden" name="wizard" value="<%=request.QueryString("wizard")%>">

<div id="wizard1"> <!--wizard 1 -->

    <div class="col-md-12" style="background-color:#f2f2f2;"><strong>Datore di lavoro</strong></div>
    
    <div class="col-md-3">
        <strong>Nome *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=nome%>" name="nome" id="nome" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Cognome *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=cognome%>" name="cognome" id="cognome" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;"><strong>Azienda/Impresa</strong></div>
    
    <div class="col-md-3">
        <strong>Ragione Sociale *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=rag_soc%>" name="rag_soc" id="rag_soc" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Partita Iva *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=piva%>" name="piva" id="piva" maxlength="11" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Indirizzo sede legale *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=indirizzo%>" required name="indirizzo" id="indirizzo" placeholder="Via" class="sm-form-control" style="width:100%!important; max-width:500px;">
    </div>
    
    <div class="col-md-3">
        <strong>Provincia / Comune / CAP *</strong>
    </div>    
    
    <div class="col-md-9">
        <%                              
        Set province = Server.CreateObject("ADODB.Recordset")
        province.ActiveConnection = MM_comuni_STRING
        province.Source = "SELECT * FROM comuni WHERE provincia IS NOT NULL GROUP BY provincia ORDER BY provincia ASC"
        province.CursorType = 0
        province.CursorLocation = 2
        province.LockType = 1
        province.Open() 
        %>  
        <select required name="provincia" id="provincia" onChange="cerca_comuni(this.value,'comune','')" style="width:auto;" class="sm-form-control">
        <option>- scegli</option>
        <%
        while not province.eof
        %>
        <option <% if province("provincia")&"" = provincia then response.Write("selected") %> value="<%=province("provincia")%>"><%=province("provincia")%></option>
        <%
        province.movenext
        wend
        %>
        </select>
        <%
        province.ActiveConnection.close
        set province = nothing
        %>    
        / 
        <select required name="comune" id="comune" onChange="cerca_cap(this.value,'cap')" style="width:auto; max-width:300px" class="sm-form-control">
    <option>- scegli prima la provincia</option>
    
    </select> / <input name="cap" id="cap" value="<%=cap%>" size="5" maxlength="5" class="sm-form-control" required />
    </div>
    
    <div class="col-md-3">
        <strong>Telefono *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=telefono%>" name="telefono" id="telefono" maxlength="50" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>E-Mail *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=email%>" name="email" id="email" maxlength="100" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>PEC *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=pec%>" name="pec" id="pec" maxlength="100" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Documento d'identità *</strong>
    </div>
    
    <div class="col-md-9">
        <input name="doc" id="doc" value="<%=doc%>" class="sm-form-control" readonly placeholder="Il tuo documento d'identità" style="width:100%!important; max-width:500px;" /> <a href="javascript:;" data-src="/doc-upload.asp?cosa=doc" class="button button-large button-3d nomargin" style="font-size:12px; height:auto; line-height:normal; padding: 2px 4px;" data-fancybox data-type="iframe"><strong>AGGIUNGI</strong></a>
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Esercente l'attivit&agrave; di *</strong>
    </div>
    
    <div class="col-md-12">
        <input value="<%=tipo_att%>" name="tipo_att" id="tipo_att" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Iscritta a *</strong>
    </div> 
    
    <div class="col-md-6">
        <input type="radio" name="iscrizione" id="iscrizione" value="0" required <% if iscrizione = "0" then response.Write("checked") %> />
        &nbsp;Cassa Edile di <input value="<%=ce%>" placeholder="Provincia Cassa" name="ce" id="ce" maxlength="100" class="sm-form-control" style="width:100%!important; max-width:150px;" /> al N&deg; <input maxlength="20" value="<%=numero_ce%>" type="text" name="numero_ce" class="sm-form-control" style="width:100%!important; max-width:80px;">
    </div>
    <div class="col-md-6">
        <input type="radio" name="iscrizione" id="iscrizione" value="1" required <% if iscrizione = "1" then response.Write("checked") %> />
        &nbsp;Edil Cassa di <input placeholder="Provincia Edil" value="<%=ec%>" name="ec" id="ec" maxlength="100" class="sm-form-control" style="width:100%!important; max-width:150px;" /> al N&deg; <input value="<%=numero_ec%>" maxlength="20" type="text" name="numero_ec" class="sm-form-control" style="width:100%!important; max-width:80px;">
    </div>
    <div class="col-md-12">
        <input type="radio" name="iscrizione" id="iscrizione" value="2" required <% if iscrizione = "2" then response.Write("checked") %> />
        &nbsp;NON iscritta a Casse Edili
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Codice Ateco secondo l&rsquo;Accordo Stato Regioni del  21/12/2011 *</strong>
    </div>
    
    <div class="col-md-12">
        <input name="ateco" value="<%=ateco%>" maxlength="20" id="ateco" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <% if request.querystring("id") = "" then %>
		<!--#include virtual="trattamento-dati.asp"-->
    
        <div class="col-md-12" style="background-color:#f2f2f2;">
            <strong>Verifica la tua identità tramite Google</strong>
        </div>
        
        <div class="col-md-12" style="padding:0;">
            <!--#include virtual="captcha.asp"-->
        </div>
        <div class="col-md-6" align="left">
            
        </div>
        <div class="col-md-6" align="right">
        	<input type="submit" id="validatebutton" style="display:none;">
            <input type="button" onclick="if(document.getElementById('trattamento1').checked==false){alert('Devi autorizzare il trattamendo dei dati, altrimenti non potremo accettare la tua richiesta.');}else{document.getElementById('validatebutton').click();}" name="submit" value="Continua" class="button button-large button-3d nomargin">
        </div>
    <% else %>
    <div class="col-md-6" align="left">
        
    </div>
    <div class="col-md-6" align="right">
        <input type="submit" name="submit" value="Continua" class="button2 button-large button-3d nomargin">
    </div>            
    <% end if %>

    
</div> <!--wizard 1 -->

<div id="wizard2"> <!--wizard 2 -->

    <div class="col-md-12" style="background-color:#f2f2f2;">
        Intendo effettuare un corso di formazione per i lavoratori in materia di igiene e  sicurezza del lavoro, nello specifico	
    </div>
    
    <div class="col-md-12">
        <strong>Riferimento normativo del corso *</strong>
        <br>
        <input value="<%=corso_normativa%>" name="corso_normativa" id="corso_normativa" maxlength="50" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Durata complessiva del corso</strong>
    </div>
    
    <div class="col-md-3">
        dal
    </div>
    
    <div class="col-md-9">
        <input type="text" value="<%=dal%>" name="dal" id="dal" class="sm-form-control data" placeholder="gg/mm/aaaa" maxlength="10" style="max-width:110px;" required />
    </div>
    
    <div class="col-md-3">
        al
    </div>
    
    <div class="col-md-9">
        <input type="text" value="<%=al%>" name="al" id="al" class="sm-form-control data" placeholder="gg/mm/aaaa" maxlength="10" style="max-width:110px;" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Soggetto Organizzatore</strong>
    </div>
    
    <div class="col-md-3">
        <strong>Rag. Soc. / Cog. Nome *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=sogg_rag_soc%>" name="sogg_rag_soc" id="sogg_rag_soc" class="sm-form-control" placeholder="Ragione Sociale" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Indirizzo *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=sogg_indirizzo%>" name="sogg_indirizzo" id="sogg_indirizzo" class="sm-form-control" placeholder="Indirizzo" style="width:100%!important; max-width:300px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Telefono *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=sogg_tel%>" name="sogg_tel" id="sogg_tel" class="sm-form-control" placeholder="Telefono" style="width:100%!important; max-width:500px;" maxlength="50" required />
    </div>
    
    <div class="col-md-3">
        <strong>E-mail *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=sogg_email%>" name="sogg_email" id="sogg_email" class="sm-form-control" placeholder="E-mail" style="width:100%!important; max-width:500px;" maxlength="100" required />
    </div>
    
    <div class="col-md-3">
        <strong>Pec *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=sogg_pec%>" name="sogg_pec" id="sogg_pec" class="sm-form-control" placeholder="Pec" style="width:100%!important; max-width:500px;" maxlength="100" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Responsabile Progetto Formativo</strong>
    </div>
    
    <div class="col-md-3">
        <strong>Rag. Soc. / Cog. Nome *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=resp_rag_soc%>" name="resp_rag_soc" id="resp_rag_soc" class="sm-form-control" placeholder="Ragione Sociale" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Indirizzo *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=resp_indirizzo%>" name="resp_indirizzo" id="resp_indirizzo" class="sm-form-control" placeholder="Indirizzo" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Telefono *</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=resp_tel%>" name="resp_tel" id="resp_tel" maxlength="50" class="sm-form-control" placeholder="Telefono" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>E-mail</strong>
    </div>
    
    <div class="col-md-9">
        <input value="<%=resp_email%>" name="resp_email" id="resp_email" maxlength="100" class="sm-form-control" placeholder="E-mail" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-6" align="left">
        <input type="button" name="submit" onClick="document.location.href='<%=pagina_url%>?id=<%=request.QueryString("id")%>&wizard=1'" value="Indietro" class="button2 button-large button-3d nomargin">
    </div>
    <div class="col-md-6" align="right">
        <input type="submit" name="submit" value="Continua" class="button2 button-large button-3d nomargin">
    </div>

</div> <!--wizard 2 -->

<div id="wizard3"> <!--wizard 3 -->

    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Dati del Corso</strong> *
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Tipologia di intervento formativo <em>(per ogni corso e&rsquo; necessario compilare un modulo)</em> *</strong>
    </div>
    
    <div class="col-md-12">
        <input name="corso" type="radio" value="0" onClick="corso_altro_script()" required <% if corso = "0" then response.Write("checked") %> />
        Corso di formazione generale&nbsp; +&nbsp; Corso di formazione specifico (specificare se rischio basso, medio o alto)
        <br>
        <input name="corso" type="radio" value="1" onClick="corso_altro_script()" required <% if corso = "1" then response.Write("checked") %> />
        Corso di formazione per preposti <br>
        <input name="corso" type="radio" value="2" onClick="corso_altro_script()" required <% if corso = "2" then response.Write("checked") %> />
        Corso di formazione per   dirigenti<br>
        <input name="corso" type="radio" value="3" onClick="corso_altro_script()" required <% if corso = "3" then response.Write("checked") %> />
        Corso di formazione per    figure di sistema (specificare quale figure)<br>
        <input name="corso" type="radio" value="4" onClick="corso_altro_script()" required <% if corso = "4" then response.Write("checked") %> />
        Corso di formazione specifico    per la mansione di (specificare quale mansione) <br>
        <input name="corso" type="radio" value="5" onClick="corso_altro_script()" required <% if corso = "5" then response.Write("checked") %> />
        Corso di addestramento    specifico (specificare)<br>
        <input name="corso" type="radio" value="6" onClick="corso_altro_script()" required <% if corso = "6" then response.Write("checked") %> />
        Corso di formazione sulle    attrezzature di lavoro (specificare quale attrezzature) <br>
        <input name="corso" type="radio" value="7" onClick="corso_altro_script()" id="corso_al" required <% if corso = "7" then response.Write("checked") %> />
        Altro (specificare)
        <input type="text" name="corso_altro" id="corso_text" placeholder="Specificare qui" style="width:100%;" class="sm-form-control" value="<%=corso_altro%>" />
    </div>
    
    <div class="col-md-3">
        <strong>Sede svolgimento corso</strong> *
    </div>
    
    <div class="col-md-9">
        <input value="<%=sede_indirizzo%>" name="sede_indirizzo" id="sede_indirizzo" placeholder="Via" class="sm-form-control" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Provincia / Comune / CAP *</strong>
    </div>
    
    
    <div class="col-md-9">
        <%                              
        Set province = Server.CreateObject("ADODB.Recordset")
        province.ActiveConnection = MM_comuni_STRING
        province.Source = "SELECT * FROM comuni WHERE provincia IS NOT NULL GROUP BY provincia ORDER BY provincia ASC"
        province.CursorType = 0
        province.CursorLocation = 2
        province.LockType = 1
        province.Open() 
        %>  
        <select name="sede_provincia" id="sede_provincia" onChange="cerca_comuni(this.value,'sede_comune','')" style="width:auto;" class="sm-form-control" required>
        <option>- scegli dall'elenco</option>
        <%
        while not province.eof
        %>
        <option <% if province("provincia")&"" = sede_provincia then response.Write("selected") %> value="<%=province("provincia")%>"><%=province("provincia")%></option>
        <%
        province.movenext
        wend
        %>
        </select>
        <%
        province.ActiveConnection.close
        set province = nothing
        %>    
        / 
        <select name="sede_comune" id="sede_comune" onChange="cerca_cap(this.value,'sede_cap')" style="width:auto;" class="sm-form-control" required>
    <option>- scegli prima la provincia</option>
    
    </select> / <input name="sede_cap" id="sede_cap" value="<%=sede_cap%>" size="10" maxlength="5" class="sm-form-control" required />
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Giorni e orari corso</strong> *
    </div>
    
    <% 
	Set rss = Server.CreateObject("ADODB.Recordset")
	rss.ActiveConnection = MM_edile_STRING
	
	trovati_giorni = 1
	
	for i = 1 to 30 
		giorno = ""
		da = ""
		a = ""		
		if request.QueryString("id") <> "" then
			rss.Source = "SELECT * FROM collaborazioni_giorni WHERE id_collaborazione = "&request.QueryString("id")&" ORDER BY giorno ASC, da ASC, a ASC"
			rss.CursorType = 0
			rss.CursorLocation = 2
			rss.LockType = 1
			rss.Open()
			ordine = 1
			while not rss.eof
				if ordine = i then
					giorno = rss("giorno")&""
					da = rss("da")&""
					a = rss("a")&""
					trovati_giorni = ordine
				end if
				ordine = ordine + 1
			rss.movenext
			wend
			rss.close
		end if	
	%>
    <div class="col-md-1 data<%=i%>" <% if i > 1 AND giorno = "" then response.Write("style='display:none;'")%>>
        <input type="text" name="data_num" value="<%=i%>" class="sm-form-control" disabled style="width:30px!important;">
    </div>
    <div class="col-md-2 data<%=i%>" <% if i > 1 AND giorno = "" then response.Write("style='display:none;'")%>>
        <input name="giorno<%=i%>" value="<%=giorno%>" type="text" class="sm-form-control data" id="giorno<%=i%>" placeholder="Giorno" maxlength="10" style="width:110px!important;">
    </div>
    <div class="col-md-2 data<%=i%>" <% if i > 1 AND giorno = "" then response.Write("style='display:none;'")%>>
        <select name="da<%=i%>" class="sm-form-control" id="da<%=i%>">
        <%
        for k = 8 to 20
        %>
        <option value="<%=right("0"&k,2)%>:00" <% if da = right("0"&k,2)&":00" then response.Write("selected") %>><%=right("0"&k,2)%>:00</option>
        <option value="<%=right("0"&k,2)%>:15" <% if da = right("0"&k,2)&":15" then response.Write("selected") %>><%=right("0"&k,2)%>:15</option>
        <option value="<%=right("0"&k,2)%>:30" <% if da = right("0"&k,2)&":30" then response.Write("selected") %>><%=right("0"&k,2)%>:30</option>
        <option value="<%=right("0"&k,2)%>:45" <% if da = right("0"&k,2)&":45" then response.Write("selected") %>><%=right("0"&k,2)%>:45</option>
        <%
        next
        %>
        </select>
    </div>
    <div class="col-md-2 data<%=i%>" <% if i > 1 AND giorno = "" then response.Write("style='display:none;'")%>>
        <select name="a<%=i%>" class="sm-form-control" id="a<%=i%>">
        <%
        for k = 8 to 20
        %>
        <option value="<%=right("0"&k,2)%>:00" <% if a = right("0"&k,2)&":00" then response.Write("selected") %>><%=right("0"&k,2)%>:00</option>
        <option value="<%=right("0"&k,2)%>:15" <% if a = right("0"&k,2)&":15" then response.Write("selected") %>><%=right("0"&k,2)%>:15</option>
        <option value="<%=right("0"&k,2)%>:30" <% if a = right("0"&k,2)&":30" then response.Write("selected") %>><%=right("0"&k,2)%>:30</option>
        <option value="<%=right("0"&k,2)%>:45" <% if a = right("0"&k,2)&":45" then response.Write("selected") %>><%=right("0"&k,2)%>:45</option>
        <%
        next
        %>
        </select>
    </div> 
    <div class="data<%=i%>" style="clear:both; height:1px; padding:0; margin:0; font-size:0.1em;<% if i > 1 AND giorno = "" then response.Write("display:none;")%>">&nbsp;</div>
    <% next %>
    
    <div align="center">
        <input type="button" id="pulsadddata" value="Aggiungi data" onClick="adddata()" class="button button-large button-3d nomargin" style="float:left; font-size:12px; height:auto; line-height:normal; padding: 2px 4px;">
        <input type="button" id="pulsdeldata" value="Elimina data" onClick="deldata()" class="button2 button-large button-3d nomargin" style=" <% if trovati_giorni = 1 then %>display:none;<% end if %> float:left; margin-left:10px; font-size:12px; height:auto; line-height:normal; padding: 2px 4px; margin-left:20px;">
        <br><br>
    </div> 
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Progetto formativo di dettaglio: (specificare nel dettaglio tutto il percorso formativo) *</strong>
    </div>
    
    <div class="col-md-12">
        <textarea required name="progetto" id="progetto" class="sm-form-control" style="width:100%!important; max-width:500px; min-height:140px;"><%=progetto%></textarea>
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Partecipanti</strong>
    </div>
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Al corso in oggetto attualmente è prevista la  partecipazione dei seguenti lavoratori (max 25)</strong>
        <br>Indicare Nome, Cognome e Data di Nascita
    </div>
    
    <% 
	trovati_partecipanti = 1
	
	for i = 1 to 30 
	
		nome = ""
		cognome = ""
		data = ""		
		if request.QueryString("id") <> "" then
			rss.Source = "SELECT * FROM collaborazioni_lav WHERE id_collaborazione = "&request.QueryString("id")&" ORDER BY nome ASC, cognome ASC, data_nascita ASC"
			rss.CursorType = 0
			rss.CursorLocation = 2
			rss.LockType = 1
			rss.Open()
			ordine = 1
			while not rss.eof
				if ordine = i then
					nome = rss("nome")&""
					cognome = rss("cognome")&""
					data = rss("data_nascita")&""
					trovati_partecipanti = ordine
				end if
				ordine = ordine + 1
			rss.movenext
			wend
			rss.close
		end if		
	%>
    <div class="col-md-1 lav<%=i%>" <% if i > 1 AND nome = "" then response.Write("style='display:none;'")%>>
        <input type="text" name="lav_num" value="<%=i%>" class="sm-form-control" disabled>
    </div>
    <div class="col-md-4 lav<%=i%>" <% if i > 1 AND nome = "" then response.Write("style='display:none;'")%>>
        <input name="nome<%=i%>" value="<%=nome%>" type="text" class="sm-form-control" id="nome<%=i%>" placeholder="Nome" maxlength="100" style="width:100%!important; max-width:500px;">
    </div>
    <div class="col-md-4 lav<%=i%>" <% if i > 1 AND nome = "" then response.Write("style='display:none;'")%>>
        <input name="cognome<%=i%>" value="<%=cognome%>" type="text" class="sm-form-control" id="cognome<%=i%>" placeholder="Cognome" maxlength="100" style="width:100%!important; max-width:500px;">
    </div>
    <div class="col-md-3 lav<%=i%>" <% if i > 1 AND nome = "" then response.Write("style='display:none;'")%>>
        <input type="text" name="data<%=i%>" value="<%=data%>" id="data<%=i%>" class="sm-form-control data_nascita" placeholder="Data di Nascita" maxlength="10" style="max-width:120px;">
    </div>  
    <% next %>
    
    <div class="clearfix"></div>
    
    <div align="center">
        <input type="button" id="pulsaddlav" value="Aggiungi lavoratore" onClick="addlav()" class="button button-large button-3d nomargin" style="float:left; font-size:12px; height:auto; line-height:normal; padding: 2px 4px;">
        <input type="button" id="pulsdellav" value="Elimina lavoratore" onClick="dellav()" class="button2 button-large button-3d nomargin" style=" <% if trovati_partecipanti = 1 then %>display:none;<% end if %> float:left; margin-left:10px; font-size:12px; height:auto; line-height:normal; padding: 2px 4px; margin-left:20px;">
        <br><br>
    </div> 
    
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Formatore/docente</strong>
    </div>
    
    <div class="col-md-3">
        <strong>Nome *</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_nome" maxlength="100" id="prof_nome" value="<%=prof_nome%>" class="sm-form-control" placeholder="Nome" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Cognome*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_cognome" maxlength="100" id="prof_cognome" value="<%=prof_cognome%>" class="sm-form-control" placeholder="Cognome" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Luogo di nascita*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_luogo_nascita" id="prof_luogo_nascita" value="<%=prof_luogo_nascita%>" class="sm-form-control" placeholder="Luogo di nascita" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Data di nascita*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_data_nascita" id="prof_data_nascita" value="<%=prof_data_nascita%>" class="sm-form-control data_nascita" placeholder="gg/mm/aaaa" style="width:100%!important; max-width:120px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Residenza (via e città)*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_residenza" id="prof_residenza" value="<%=prof_residenza%>" class="sm-form-control" placeholder="Es. Via Cavour 12, Lecce" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>Telefono*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_tel" id="prof_tel" value="<%=prof_tel%>" maxlength="50" class="sm-form-control" placeholder="Possibilmente cellulare" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-3">
        <strong>E-mail*</strong>
    </div>
    
    <div class="col-md-9">
        <input name="prof_email" id="prof_email" value="<%=prof_email%>" maxlength="100" class="sm-form-control" placeholder="E-mail" style="width:100%!important; max-width:500px;" required />
    </div>
    
    <div class="col-md-12">
        <strong>Allega la <a href="/public/moduli/modulo_collaborazione_b.doc" name="modb" target="_blank">seguente dichiarazione del docente (scarica qui)</a> firmato dal docente e con il documento d'identità allegato *</strong>
        <br>
        <input value="<%=modulo_b%>" name="modulo_b" id="modulo_b" class="sm-form-control" readonly placeholder="Modulo di collaborazione" style="width:100%!important; max-width:500px;" /> <a href="javascript:;" data-src="/doc-upload.asp?cosa=modulo_b" class="button button-large button-3d nomargin" style="font-size:12px; height:auto; line-height:normal; padding: 2px 4px;" data-fancybox data-type="iframe"><strong>AGGIUNGI</strong></a>
        
    </div>
    
    <div class="col-md-6" align="left">
        <input type="button" name="submit" onClick="document.location.href='<%=pagina_url%>?id=<%=request.QueryString("id")%>&wizard=2'" value="Indietro" class="button2 button-large button-3d nomargin">
    </div>
    <div class="col-md-6" align="right">
        <input type="submit" name="submit" value="Continua" class="button2 button-large button-3d nomargin">
    </div>

</div> <!--wizard 3 -->

<div id="wizard4"> <!--wizard 4-->

    <div class="col-md-12" style="background-color:#f2f2f2; font-size:15px; color:#C90000" align="center">
        <strong>CONSAPEVOLE</strong>
    </div>   
    <div class="col-md-12">
        delle sanzioni penali nel caso di dichiarazioni non veritiere e falsità negli atti (art. 75 e art. 76 del DPR n. 445/2000)
    </div>  
    
    <div class="col-md-12" style="background-color:#f2f2f2; font-size:15px; color:#d35d13" align="center">
        <strong>CHIEDE</strong>
    </div>   
    <div class="col-md-12">
        ai sensi dell'art. 37 comma 12 del D.Lgs 81/2008 e s.m.i. e dell' Accordo Stato Regioni del 21/12/2011,<strong> LA COLLABORAZIONE DI Formedil Lecce</strong>
    </div>    
    
    <div class="col-md-12" style="background-color:#f2f2f2; color:#d35d13" align="left">
        <strong>A tale scopo si impegna, pena la decadenza della collaborazione in oggetto, a</strong>
    </div>
    
    <div class="col-md-12">
        <ul>
        <li>rispettare le eventuali indicazioni e suggerimenti erogati dal Formedil Lecce in fase preliminare</li>
        <li>consentire, in fase di effettuazione del corso, eventuali verifiche da parte di Formedil Lecce</li>
        <li>informare tempestivamente Formedil Lecce in caso di annullamento del corso e/o di variazioni di sede e/o di orari</li>
        <li>trasmettere, se richiesti da Formedil Lecce, copia dei seguenti documenti attestanti l'avvenuta effettuazione del corso</li>
        <li>a) copia del registro delle presenze debitamente compilato e sottoscritto dai docenti e dal soggetto formatore</li>
        <li>b) copia dei documenti di identità dei partecipanti al corso</li>
        <li>c) copia delle risultanze delle verifiche intermedie e/o finali (se richieste dalla legge o se effettuate)</li>
        </ul>
    </div>
        
    <div class="col-md-12" style="background-color:#f2f2f2;">
        <strong>Eventuali note e/o comunicazioni</strong>
    </div>
    
    <div class="col-md-12">
        <textarea name="note" id="note" class="sm-form-control" style="width:100%!important; max-width:500px; min-height:140px;"></textarea>
    </div>
    
    <div class="col-md-6" align="left">
        <input type="button" name="submit" onClick="document.location.href='<%=pagina_url%>?id=<%=request.QueryString("id")%>&wizard=3'" value="Indietro" class="button2 button-large button-3d nomargin">
    </div>
    <div class="col-md-6" align="right">    
    	<input type="submit" id="validatebutton2" style="display:none;">
        <input type="button" value="Invia Modulo" onClick="if(confirm('Sicuro?\n\nInoltrerai la tua domanda di collaborazione e non sarà più possibile modificarla.')){document.getElementById('validatebutton2').click();}" class="button button-large button-3d nomargin">
    </div>

</div> <!--wizard 4 -->
</form>

<% end if 'if request.QueryString("msg") = "ok" then %>

<br><br>

</div> <!--schermata -->      

<style>
.content-wrap .container{
	padding:0;
}
.schermata div{
	padding:10px;
}
#wizard1, #wizard2, #wizard3, #wizard4{
	padding:0;
}
.schermata .sm-form-control{
	display:inline!important;
}
.schermata ul{
	padding-left:30px;
	margin-bottom:0;
}
input, select, textarea{
	cursor:pointer;
	font-size:15px!important;
}

<% if request.QueryString("wizard") = "" OR request.QueryString("wizard") = "1" then %>
#wizard2, #wizard3, #wizard4{
	display:none;
}
<% elseif request.QueryString("wizard") = "2" then %>
#wizard1, #wizard3, #wizard4{
	display:none;
}
<% elseif request.QueryString("wizard") = "3" then %>
#wizard1, #wizard2, #wizard4{
	display:none;
}
<% elseif request.QueryString("wizard") = "4" then %>
#wizard1, #wizard2, #wizard3{
	display:none;
}
<% end if %>
</style>       
    
<!--#include virtual="inc_bot.asp"-->

<script src="/js/ui/jquery.ui.core.js"></script>
<script src="/js/ui/jquery.ui.widget.js"></script>
<script src="/js/ui/jquery.ui.datepicker.js"></script>
<link rel="stylesheet" href="/js/themes/base/jquery.ui.all.css">
<script type="text/javascript">
function cerca_comuni(codice,campo,valore){
	if(codice==''){
		window.alert('Scegli una provincia!');
	}
	else{
		$('#'+campo).load('/ajax_comuni.asp?codice='+codice+'&comune='+encodeURIComponent(valore));	
	}
}

function checkcaptcha(){
	if($('#captcha').val()!=""){
		var request = $.ajax({
			url: "/check_captcha.asp",
			type: "POST",
			data: { val : $('#captcha').val() },
			dataType: "html"
		});
		request.done(function( msg ) {
			if(msg==0){
				$('#captcha_user').html("<span style=color:#222222>CODICE ERRATO</span>");
			}
			else{
				$('#captcha_user').html("<span style=color:#d35d13>Codice CORRETTO</span>");
				$('#captcha').prop("readonly",true);
				$('#captcha').css("background-color","#f2f2f2");
			}
		});	
	}
	else{
		$('#captcha_user').html("");
	}	
}

$('#captcha').on('keyup',function(){
	checkcaptcha();
});

function cerca_cap(comune,campo){
	var request = $.ajax({
		url: "/ajax_cap.asp",
		type: "POST",
		data: { comune : comune },
		dataType: "html"
	});
	request.done(function( msg ) {
		$('#'+campo).val(msg);
	});		
}

var lav_attuale = <%=trovati_partecipanti%>;
var lav_limite = 30;

function addlav(){
	if(lav_attuale >= 1 && lav_attuale < lav_limite){
		if($('#nome'+lav_attuale).val()=='' || $('#cognome'+lav_attuale).val()=='' || $('#data'+lav_attuale).val()==''){
			alert('Compila il lavoratore precedente');
		}
		else{

			$('#nome'+lav_attuale).prop('required',true);
			$('#cognome'+lav_attuale).prop('required',true);
			$('#data'+lav_attuale).prop('required',true);
			
			lav_attuale = lav_attuale + 1;
			
			$('.lav'+lav_attuale).css('display','inherit');
			$('#pulsdellav').css('display','inherit');
		}
		
		if(lav_attuale == lav_limite){
			$('#pulsaddlav').css('display','none');
		}
	}
}

function dellav(){
	if(lav_attuale > 1){
		
		$('#nome'+lav_attuale).val("");
		$('#cognome'+lav_attuale).val("");
		$('#data'+lav_attuale).val("");
			
		$('.lav'+lav_attuale).css('display','none');
		lav_attuale = lav_attuale - 1;
		
		$('#nome'+lav_attuale).prop('required',false);
		$('#cognome'+lav_attuale).prop('required',false);
		$('#data'+lav_attuale).prop('required',false);		
		
		$('#pulsaddlav').css('display','inherit');
		
		if(lav_attuale==1){
			$('#pulsdellav').css('display','none');
		}
	}
}

var data_attuale = <%=trovati_giorni%>;
var data_limite = 30;

function adddata(){
	if(data_attuale >= 1 && data_attuale < data_limite){
		if($('#giorno'+data_attuale).val()=='' || $('#da'+data_attuale).val()=='' || $('#a'+data_attuale).val()==''){
			alert('Compila il giorno precedente');
		}
		else{

			$('#giorno'+data_attuale).prop('required',true);
			$('#da'+data_attuale).prop('required',true);
			$('#a'+data_attuale).prop('required',true);
			
			data_attuale = data_attuale + 1;
			
			$('.data'+data_attuale).css('display','inherit');
			$('#pulsdeldata').css('display','inherit');
		}
		
		if(data_attuale == data_limite){
			$('#pulsadddata').css('display','none');
		}
	}
}

function deldata(){
	if(data_attuale > 1){
		
		$('#giorno'+data_attuale).val("");
		$('#da'+data_attuale).val("");
		$('#a'+data_attuale).val("");
			
		$('.data'+data_attuale).css('display','none');
		data_attuale = data_attuale - 1;
		
		$('#giorno'+data_attuale).prop('required',false);
		$('#da'+data_attuale).prop('required',false);
		$('#a'+data_attuale).prop('required',false);		
		
		$('#pulsadddata').css('display','inherit');
		
		if(data_attuale==1){
			$('#pulsdeldata').css('display','none');
		}
	}
}

function setdocentedoc(){
	var href = '/allegadoc.asp?campo=modulo_b';
	$.fancybox.open({
		src: href,
		type: 'iframe',	
		opts: {}
	});
}

function setcfdatasrc(){
	var href = '/allegadocfirm.asp?cf='+document.getElementById('cf').value;
	$.fancybox.open({
		src: href,
		type: 'iframe',	
		opts: {}
	});
}

$(function() {
	$( ".data_nascita" ).datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: '-85:-18',
		defaultDate: new Date(1970,1,1)
	});
	$( ".data" ).datepicker({
		changeMonth: true,
		changeYear: true,
		yearRange: '-0:+1',
		defaultDate: new Date(<%=year(date())%>,<%=month(date())+1%>,<%=day(date())%>)
	});	
	
	if(document.getElementById('provincia').value != ''){
		cerca_comuni(document.getElementById("provincia").value,"comune","<%=comune%>");
	} 
	
	if(document.getElementById('sede_provincia').value != ''){
		cerca_comuni(document.getElementById("sede_provincia").value,"sede_comune","<%=sede_comune%>");
	} 			
	
	for(var i=lav_attuale;i<=lav_limite;i++){
		if(document.getElementById('nome'+i).value!=""){
			$('.lav'+i).css('display','inherit');
		}
	}
	
	checkcaptcha();
	
	$('#formiscrizione').validate();
});

function corso_altro_script(){
	
	if(document.getElementById('corso_al').checked==true){
		document.getElementById('corso_text').style.visibility = '';
	}
	else{
		document.getElementById('corso_text').value = '';
		document.getElementById('corso_text').style.visibility = 'hidden';
	}
}
window.onload = function(){corso_altro_script()};
</script>
<% if request.QueryString("id") = "" then %>
	<!--#include virtual="trattamento-dati-jquery.asp"-->
<% end if %>