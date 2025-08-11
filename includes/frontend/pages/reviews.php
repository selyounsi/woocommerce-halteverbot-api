<?php
    use Utils\ReviewsSettings;

    if (!defined('ABSPATH')) {
        exit;
    }
    if (!ReviewsSettings::isEnabled()) {
        header('Location: /');
    }

    $settings = ReviewsSettings::getSettings();
    $email_logo_id = get_option('woocommerce_email_header_image');
?>
<!DOCTYPE html>
<html lang="en" >
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Teilen Sie uns Ihre Erfahrung mit! – Kundenbewertung</title>
        <meta name="description" content="Geben Sie uns Ihre Bewertung und teilen Sie Ihre Erfahrung mit unserem Service. Helfen Sie uns, besser zu werden!" />
        <meta property="og:title" content="Teilen Sie uns Ihre Erfahrung mit! – Kundenbewertung" />
        <meta property="og:description" content="Geben Sie uns Ihre Bewertung und teilen Sie Ihre Erfahrung mit unserem Service. Helfen Sie uns, besser zu werden!" />
        <meta property="og:type" content="website" />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo WHA_PLUGIN_ASSETS_URL ?>/plugins/notiflix/notiflix.min.css">
        <script src="<?php echo WHA_PLUGIN_ASSETS_URL ?>/plugins/notiflix/notiflix.min.js"></script>

        <style>
            .star-rating {
                font-size: 0;
                white-space: nowrap;
                display: inline-block;
                width: 250px;
                height: 50px;
                overflow: hidden;
                position: relative;
                background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMjBweCIgaGVpZ2h0PSIyMHB4IiB2aWV3Qm94PSIwIDAgMjAgMjAiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDIwIDIwIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cG9seWdvbiBmaWxsPSIjREREREREIiBwb2ludHM9IjEwLDAgMTMuMDksNi41ODMgMjAsNy42MzkgMTUsMTIuNzY0IDE2LjE4LDIwIDEwLDE2LjU4MyAzLjgyLDIwIDUsMTIuNzY0IDAsNy42MzkgNi45MSw2LjU4MyAiLz48L3N2Zz4=');
                background-size: contain;
            }
            .star-rating i {
                opacity: 0;
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 20%;
                z-index: 1;
                background: url('data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMjBweCIgaGVpZ2h0PSIyMHB4IiB2aWV3Qm94PSIwIDAgMjAgMjAiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDIwIDIwIiB4bWw6c3BhY2U9InByZXNlcnZlIj48cG9seWdvbiBmaWxsPSIjRkZERjg4IiBwb2ludHM9IjEwLDAgMTMuMDksNi41ODMgMjAsNy42MzkgMTUsMTIuNzY0IDE2LjE4LDIwIDEwLDE2LjU4MyAzLjgyLDIwIDUsMTIuNzY0IDAsNy42MzkgNi45MSw2LjU4MyAiLz48L3N2Zz4=');
                background-size: contain;
            }
            .star-rating input {
                -moz-appearance: none;
                -webkit-appearance: none;
                opacity: 0;
                display: inline-block;
                width: 20%;
                height: 100%;
                margin: 0;
                padding: 0;
                z-index: 2;
                position: relative;
            }
            .star-rating input:hover+i,
            .star-rating input:checked+i {
                opacity: 1;
            }
            .star-rating i~i {
                width: 40%;
            }
            .star-rating i~i~i {
                width: 60%;
            }
            .star-rating i~i~i~i {
                width: 80%;
            }
            .star-rating i~i~i~i~i {
                width: 100%;
            }

            .card {
                width: 320px;
            }

            .branding {
                max-width: 240px;
            }

            @media (min-width: 480px) {
                .card {
                    width: 480px;
                }

                .branding {
                    max-width: 300px;
                }
            }
        </style>
    </head>
    <body class="align-items-center d-flex justify-content-center vh-100">
        
        <div class="text-center">

            <?php 
    
                if ( $email_logo_id ) {
                    echo '<img class="branding mb-4" src="' . esc_url( $email_logo_id ) . '" alt="Shop Logo">';
                }
            ?>

            <div class="card " id="card_rating">
                <div class="card-body text-center py-5 px-4">
                    <h1 class="h4"><?php echo $settings["card_rating"]["headline"] ?></h1>
                    <form>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="1"><i></i>
                            <input type="radio" name="rating" value="2"><i></i>
                            <input type="radio" name="rating" value="3"><i></i>
                            <input type="radio" name="rating" value="4"><i></i>
                            <input type="radio" name="rating" value="5"><i></i>
                        </div>
                        <div class="mb-5">
                            <span id="rating-value" class="h4">0</span> von 5 Sternen
                        </div>

                        <label for="referral_source" class="mb-2">Wie haben Sie uns gefunden?</label>
                        <select class="form-select mb-3" id="referral_source" name="referral_source">
                            <option selected>Bitte wählen Sie eine Option (optional)</option>
                            <?php foreach ($settings['card_rating']['referral_source'] as $index => $source): ?>
                                <option value="<?php echo $source; ?>"><?php echo $source; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <?php if($settings["card_rating"]["show_textarea"]): ?>
                            <div class="mb-3">
                                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" placeholder="<?php echo $settings["card_rating"]["textarea_placeholder"] ?>"></textarea>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-danger w-100"><?php echo $settings["card_rating"]["submit_button_text"] ?></button>
                    </form>
                </div>
            </div>

            <div class="card d-none" id="card_google">
                <div class="card-body text-center py-5 px-4">
                    <h1 class="h4"><?php echo $settings["card_google"]["headline"] ?></h1>
                    <p><?php echo $settings["card_google"]["text"] ?></p>
                    <a class="btn btn-danger" target="_blank" href="<?php echo $settings["card_google"]["button_link"] ?>" title="<?php echo $settings["card_google"]["button_text"] ?>">
                        <?php echo $settings["card_google"]["button_text"] ?>
                    </a>
                </div>
            </div>

            <div class="card d-none" id="card_end">
                <div class="card-body text-center py-5 px-4">
                    <h1 class="h4"><?php echo $settings["card_end"]["headline"] ?></h1>
                    <p><?php echo $settings["card_end"]["text"] ?></p>
                    <a class="btn btn-danger" href="/" title="Zur Website">Zur Website</a>
                </div>
            </div>

        </div>

        <script>
            const starRating = document.querySelector('.star-rating');
            const ratingValue = document.getElementById('rating-value');
            const form = document.querySelector('form');
            const submitButton = form.querySelector('button[type="submit"]');
            const textarea = form.querySelector('textarea');

            const cardRating = document.getElementById('card_rating');
            const cardGoogle = document.getElementById('card_google');
            const cardEnd = document.getElementById('card_end');

            starRating.addEventListener('change', function(e) {
                ratingValue.textContent = e.target.value;
            });

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const rating = form.querySelector('input[name="rating"]:checked');
                if (!rating) {
                    Notiflix.Notify.failure('Bitte wähle eine Bewertung aus.');
                    return;
                }

                const referral = form.querySelector('select[name="referral_source"]');
                // if (!referral || referral.value === 'Wählen Sie eine Option') {
                //     Notiflix.Notify.failure('Bitte wählen Sie eine Option aus, wie Sie uns gefunden haben.');
                //     return;
                // }

                // const confirmed = await confirmAsync(
                //     'Bewertung abschicken',
                //     'Möchten Sie diese Bewertung wirklich abschicken?'
                // );

                // if (!confirmed) return;

                // Formatiere Daten als URLSearchParams
                const data = new URLSearchParams();
                data.append('action', 'wha_create_review');
                data.append('rating', rating.value);
                data.append('referral_source', referral.value);
                if (textarea) {
                    data.append('feedback', textarea.value);
                }

                // Prüfe, ob order_id in der URL enthalten ist
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('order_id')) {
                    const orderId = urlParams.get('order_id');
                    if (orderId) {
                        data.append('order_id', orderId);
                    }
                }

                try {
                    Notiflix.Loading.standard('Loading...', {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                    });

                    const response = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: data.toString(),
                    });

                    if (!response.ok) throw new Error(`Fehler: ${response.status}`);

                    const result = await response.json();

                    if (result.status === 'error') {
                        Notiflix.Notify.failure(result.message);
                        return;
                    }

                    // Nach Absenden Karte wechseln:
                    cardRating.classList.add('d-none');
                    if (parseInt(rating.value, 10) === 5) {
                        cardGoogle.classList.remove('d-none');
                    } else {
                        cardEnd.classList.remove('d-none');
                    }

                    // Formular zurücksetzen
                    form.reset();
                    ratingValue.textContent = '0';

                } catch (error) {
                    Notiflix.Notify.failure(`Fehler beim Senden: ${error.message}`);
                    console.log(error.message);
                } finally {
                    Notiflix.Loading.remove();
                }
            });

            /**
             * confirmAsync
             */
            function confirmAsync(title, message, okText = 'Ja', cancelText = 'Nein') {
                return new Promise((resolve) => {
                    Notiflix.Confirm.show(
                        title,
                        message,
                        okText,
                        cancelText,
                        () => resolve(true),
                        () => resolve(false)
                    );
                });
            }
        </script>
    </body>
</html>
