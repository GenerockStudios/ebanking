 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
 </head>
 <body>
<?php 
    echo password_hash('MonMotDePasseSuperSécurisé!', PASSWORD_BCRYPT);

?>
 </body>
 </html>