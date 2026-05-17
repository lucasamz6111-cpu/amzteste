    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SPX Rastreamento</title>

    <style>

        *{
        margin:0;
        padding:0;
        box-sizing:border-box;
        }

        body{
        width:100%;
        height:100vh;
        overflow:hidden;
        background:#0f0f0f;
        font-family:Arial, sans-serif;
        color:white;
        }

        .topbar{
        width:100%;
        height:60px;
        background:#000;

        display:flex;
        align-items:center;
        justify-content:space-between;

        padding:0 20px;

        border-bottom:1px solid #222;
        }

        .logo{
        font-size:18px;
        font-weight:bold;
        }

        .buttons{
        display:flex;
        gap:10px;
        }

        button{
        border:none;
        padding:10px 15px;
        border-radius:8px;
        cursor:pointer;
        font-weight:bold;

        background:#2563eb;
        color:white;
        }

        button:hover{
        opacity:.9;
        }

        .frame-area{
        width:100%;
        height:calc(100vh - 60px);
        position:relative;
        }

        iframe{
        width:100%;
        height:100%;
        border:none;
        background:white;
        overflow:hidden;
        }

        .loading{

        position:absolute;
        inset:0;

        display:flex;
        align-items:center;
        justify-content:center;

        background:#111;

        z-index:5;

        flex-direction:column;

        gap:15px;
        }

        .spinner{
        width:50px;
        height:50px;

        border:5px solid #333;
        border-top:5px solid #2563eb;

        border-radius:50%;

        animation:giro 1s linear infinite;
        }

        @keyframes giro{
        100%{
            transform:rotate(360deg);
        }
        }

        .erro{

        position:absolute;
        inset:0;

        display:none;

        align-items:center;
        justify-content:center;

        flex-direction:column;

        background:#111;

        gap:15px;

        z-index:10;
        }

        .erro h1{
        font-size:28px;
        }

        .erro p{
        opacity:.8;
        }

    </style>
    </head>

    <body>

    <div class="topbar">

        <div class="logo">
        Rastreamento SPX
        </div>

        <div class="buttons">

        <button onclick="recarregar()">
            Recarregar
        </button>

            
        </button>

        </div>

    </div>

    <div class="frame-area">

        <div class="loading" id="loading">

        <div class="spinner"></div>

        <div>
            Carregando rastreamento...
        </div>

        </div>

        <div class="erro" id="erro">

        <h1>
            Site bloqueou iframe
        </h1>

        <p>
            Abra diretamente no navegador.
        </p>

        <button onclick="abrirOriginal()">
            Abrir Site
        </button>

        </div>

        <iframe
        id="frame"
        sandbox="
            allow-scripts
            allow-same-origin
            allow-forms
            allow-popups
            allow-modals
            allow-top-navigation
        "
        allowfullscreen
        ></iframe>

    </div>

    <script>

        const url =
        "https://spx.com.br/track?";

        const frame =
        document.getElementById("frame");

        const loading =
        document.getElementById("loading");

        const erro =
        document.getElementById("erro");

        function carregar(){

        loading.style.display = "flex";

        erro.style.display = "none";

        frame.src = url;
        }

        frame.onload = () => {

        console.log("Carregado.");

        setTimeout(() => {

            loading.style.display = "none";

        }, 1500);
        };

        frame.onerror = () => {

        loading.style.display = "none";

        erro.style.display = "flex";
        };

        function recarregar(){

        carregar();
        }

        function abrirOriginal(){

        window.open(
            url,
            "_blank"
        );
        }

        carregar();

    </script>

    </body>
    </html>