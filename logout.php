<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Détruire la session
session_start();
session_unset();
session_destroy();

// Rediriger vers la page d'accueil
header("Location: index.php");
exit;
?>