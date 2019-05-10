<html>
<head>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 90%;
}

td, th {
    border: 0px solid #dddddd;
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}

.titulo{
    text-align:center !important;
    background-color: #dddddd;
}
.cuerpo{
    text-align:justify;
    width: 70%;
    font-size:16px;
}
.footer{
    text-align:center !important;
    background-color: #dddddd;
    font-size:12px;
}
.hola{
    text-align:left !important;
    font-weight:bold;
    color: #000 !important;
}
.problemas{
    font-size:12px;
}
.firma{
    font-weight:bold;
}

p{
    color:	#737373;
}
.myButton {
    
	-moz-box-shadow: 0px 0px 0px 2px #9fb4f2;
	-webkit-box-shadow: 0px 0px 0px 2px #9fb4f2;
	box-shadow: 0px 0px 0px 2px #9fb4f2;
	background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #7892c2), color-stop(1, #476e9e));
	background:-moz-linear-gradient(top, #7892c2 5%, #476e9e 100%);
	background:-webkit-linear-gradient(top, #7892c2 5%, #476e9e 100%);
	background:-o-linear-gradient(top, #7892c2 5%, #476e9e 100%);
	background:-ms-linear-gradient(top, #7892c2 5%, #476e9e 100%);
	background:linear-gradient(to bottom, #7892c2 5%, #476e9e 100%);
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#7892c2', endColorstr='#476e9e',GradientType=0);
	background-color:#7892c2;
	-moz-border-radius:10px;
	-webkit-border-radius:10px;
	border-radius:10px;
	border:1px solid #7c94d6;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:19px;
	padding:12px 37px;
	text-decoration:none;
	text-shadow:0px 1px 0px #364670;

}
.myButton:hover {
	background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #476e9e), color-stop(1, #7892c2));
	background:-moz-linear-gradient(top, #476e9e 5%, #7892c2 100%);
	background:-webkit-linear-gradient(top, #476e9e 5%, #7892c2 100%);
	background:-o-linear-gradient(top, #476e9e 5%, #7892c2 100%);
	background:-ms-linear-gradient(top, #476e9e 5%, #7892c2 100%);
	background:linear-gradient(to bottom, #476e9e 5%, #7892c2 100%);
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#476e9e', endColorstr='#7892c2',GradientType=0);
	background-color:#476e9e;
}
.myButton:active {
	position:relative;
	top:1px;
}

</style>
</head>

    <body>
            <table>
            <tr>
                <td class="titulo" colspan="3"><h2>{{$empresa_name}}<h2></td>
            </tr>

            <tr>
                <td ></td> 
                <td class="cuerpo">
                <p class="hola">Hola!</p>
                <p>Usted está recibiendo este correo electrónico porque hemos recibido una solicitud de restablecimiento de contraseña de su cuenta.</p>
                <center><a href="{{ $link_reset }}" class="myButton">Restablecer contraseña</a></center>
                <p>Si no solicitó un restablecimiento de contraseña, no se requiere ninguna acción adicional.<p>
                <p>Saludos,</p>
                <p>{{$empresa_name}}</p>    
                <br>
                <hr/>
                <br>
                <p class="problemas">Si tiene problemas para hacer clic en el botón "Restablecer la contraseña", copie y pegue la siguiente URL en su navegador web:</p>
                <p class="problemas">{{ $link_reset }}</p>
                </td> 
                <td ></td>
            </tr>
            
            <tr>
                <td  class="footer" colspan="3">© 2018 {{$empresa_name}}. Todos los derechos reservados.</td>
            </tr>
            </table>
            
            
    </body>


</html>


