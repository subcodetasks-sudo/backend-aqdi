<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>عقدي</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="card">
        <!-- Banner Image -->
        <img src="{{ asset('website/asset/images/30-min.png') }}" alt="Banner" class="banner-img" />

        <!-- Buttons -->
        <div class="buttons">
            <!-- Website Button -->
            <a href="https://aqdi.sa/" class="btn btn-website">موقعنا الإلكتروني</a>

            <!-- WhatsApp Button -->
            <a href="https://wa.me/966561998918" class="btn btn-whatsapp" target="_blank">
                <svg class="wa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor">
                    <path d="M16 0C7.164 0 0 7.163 0 16c0 2.822.736 5.472 2.023 7.774L0 32l8.438-2.01A15.938 15.938 0 0 0 16 32c8.836 0 16-7.163 16-16S24.836 0 16 0zm0 29.333a13.27 13.27 0 0 1-6.761-1.843l-.485-.288-5.008 1.194 1.224-4.862-.316-.498A13.24 13.24 0 0 1 2.667 16C2.667 8.636 8.636 2.667 16 2.667S29.333 8.636 29.333 16 23.364 29.333 16 29.333zm7.27-9.878c-.398-.199-2.355-1.162-2.72-1.294-.366-.133-.632-.199-.898.199-.265.398-1.03 1.294-1.262 1.56-.232.265-.465.298-.863.1-.398-.199-1.68-.619-3.2-1.975-1.183-1.055-1.982-2.357-2.213-2.755-.232-.398-.025-.614.174-.812.179-.178.398-.465.597-.697.199-.232.265-.398.398-.664.133-.265.066-.498-.033-.697-.1-.199-.898-2.165-1.23-2.963-.324-.778-.652-.672-.898-.684l-.765-.013c-.265 0-.697.1-1.063.498-.366.398-1.396 1.363-1.396 3.326 0 1.962 1.43 3.859 1.628 4.124.199.265 2.813 4.295 6.816 6.024.953.411 1.696.657 2.274.841.956.305 1.826.262 2.514.159.767-.114 2.355-.963 2.688-1.893.332-.93.332-1.727.232-1.893-.099-.166-.365-.265-.764-.464z"/>
                </svg>
                تواصل معنا عبر واتساب
            </a>
        </div>
    </div>
</body>
</html>

<style>
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    overflow: hidden;
}

body {
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-end;
    background: transparent;
    font-family: "Segoe UI", "Cairo", Tahoma, Arial, sans-serif;
}

.card {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100vh;
    padding: 10px;
}

.banner-img {
    width: 100%;
    display: block;
    flex: 1;
    min-height: 0;
}

.buttons {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 20px;
    background: transparent;
    flex-shrink: 0;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 20px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease,
        filter 0.15s ease;
    cursor: pointer;
    letter-spacing: 0.3px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(11, 110, 47, 0.5);
    filter: brightness(1.06);
}

.btn:active {
    transform: translateY(0);
    box-shadow: none;
    filter: brightness(0.95);
}

.btn-website {
    background: linear-gradient(135deg, #48b862 0%, #0b6e2f 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(11, 110, 47, 0.4);
}

.btn-whatsapp {
    background: linear-gradient(135deg, #48b862 0%, #0b6e2f 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(11, 110, 47, 0.4);
}

.wa-icon {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .card {
        height: 100vh;
        width: 100%;
        max-width: 620px;
        margin-inline: auto;
    }

    .banner-img {
        flex: none;
        height: calc(100vh - 100px);
    }

    .buttons {
        flex-direction: row;
        gap: 20px;
    }

    .btn {
        flex: 1;
        width: 100%;
    }
}
</style>