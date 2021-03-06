<?php

require 'vendor/autoload.php';

use Mpociot\BotMan\BotManFactory;
use React\EventLoop\Factory;
use Mpociot\BotMan\Messages\Message;

$loop = Factory::create();

$etat = 0;
$compteur = 0;
$points = 0;
$ligne =' ';
$number = 0;
$http = '';
$nom = '';

$fichier = fopen('nb.txt', 'r+');
fseek($fichier, 0);
$var = intval(fgets($fichier));
fclose($fichier);

$var = 4;

$botman = BotManFactory::createForRTM([
   'slack_token' => 'xoxb-197653180870-OuMUI3e8w4KaNHBWGvtLpJML'
], $loop);

$botman->hears('Warbot begin',function($bot) use (&$var, &$etat, &$compteur, &$ligne, &$number, &$fichier, &$compteur){
   $bot->reply('Début du quiz :');
   $fichier = fopen('figurines.txt', 'r+');
   rewind($fichier);
   fseek($fichier, 0);
   $etat = 2;
   $number = rand(0,$var-1);
   for($i = 0; $i < $number; $i++){
       $ligne = fgets($fichier);
       $ligne = fgets($fichier);
   };
   $ligne = fgets($fichier);
   $compteur = $compteur + 1;
   $bot->reply('Question n°' . $compteur);
   $bot->typesAndWaits(1);
   $bot->reply($ligne);
   $bot->typesAndWaits(1);
   $bot->reply('Qui est-ce ?');
   $bot->reply('(Pour répondre tapez : "Cette figurine est ...".)');
   $ligne = fgets($fichier);
   fclose($fichier);
});

$botman->hears('Cette figurine est {answer}',function($bot, $answer) use (&$var, &$etat, &$points, &$ligne, &$number, &$compteur){
    $answer = trim($answer);
    $ligne = trim($ligne);
    if($ligne === $answer){
        $points = $points + 1;
        $bot->typesAndWaits(1);
        $bot->reply('Bonne réponse !');
        $bot->typesAndWaits(1);
        $bot->reply('Ton score est de : ' . $points);
    }
    else if($etat == 2 and $answer == 'End'){
        $etat = 0;
        $bot->reply("Fin du jeu !");
        $bot->typesAndWaits(1);
        $bot->reply("Ton score est de " . $points . ' sur ' . $compteur);
        $compteur = 0;
        $points = 0;
    }
    else if ($etat != 2){
        $bot->typesAndWaits(1);
        $bot->reply("Pour commencer une partie, tapez : \"Warbot begin\".");
    }
    else if ($etat == 2 and $answer != $ligne){
        $bot->typesAndWaits(1);
        $bot->reply("Ce n'est pas la bonne réponse.");
        $bot->typesAndWaits(1);
        $bot->reply('La bonne réponse était : ' . $ligne);
    }
    $bot->typesAndWaits(1);
    $bot->reply('Taper "Ok" pour continuer et "End" pour finir.');
});

$botman->hears("{continue}",function($bot, $continue) use(&$var, &$etat, &$points, &$ligne, &$number, &$compteur) {
    if ($continue == "Ok" and $etat == 2) {
        $fichier = fopen('figurines.txt', 'r+');
        rewind($fichier);
        fseek($fichier, 0);
        $number = rand(0,$var-1);
        for($i = 0; $i < $number; $i++){
            $ligne = fgets($fichier);
            $ligne = fgets($fichier);
        };
        $ligne = fgets($fichier);
        $compteur = $compteur + 1;
        $bot->reply('Question n°' . $compteur);
        $bot->typesAndWaits(1);
        $bot->reply($ligne);
        $bot->typesAndWaits(1);
        $bot->reply('Qui est-ce ?');
        $bot->reply('(Pour répondre tapez : "Cette figurine est ...".)');
        $ligne = fgets($fichier);
        fclose($fichier);
    }
    else if($continue == "End" and $etat == 2){
        $etat = 0;
        $bot->reply("Fin du jeu !");
        $bot->typesAndWaits(1);
        $bot->reply("Ton score est de " . $points . ' sur ' . $compteur);
        $compteur = 0;
        $points = 0;
    }
    else if($etat == 0 and $continue != "Ajout d'une figurine"){
        $bot->reply('Pour commencer une nouvelle partie, tapez "Warbot begin".');
    };
});

$botman->hears("Ajout d'une figurine", function($bot) use (&$etat){
    if ($etat == 0){
       $bot-> reply('Tapez l\'URL de l\'image comme ceci : "URL : (URL de l\'image)"');
       $etat = 3;
    }
    else if ($etat == 1 or $etat == 2){
        $bot->reply('Vous ne pouvez pas ajouter une nouvelle figurine pendant une partie.');
    };
});

$botman->hears("URL : {url}", function($bot, $url) use (&$etat, &$nom, &$http){
    if ($etat == 3) {
        $http = trim($url);
        if (preg_match('#http(s)?://#',$http) and preg_match('#.jpg|.jpeg|.png#', $http)) {
            $bot->reply('Tapez maintenant le nom (en un seul mot) de la figurine, les majuscules étant importantes, comme ceci : "Nom : (Nom de la figurine)".');
        }
        else{
            $bot->reply('Ce n\'est pas une URL valide.');
        };
        $etat = 4;
    }
    else if ($etat == 1 or $etat == 2){
        $bot->reply('Vous ne pouvez pas ajouter une nouvelle figurine pendant une partie.');
    }
});

$botman->hears("Nom : {name}", function($bot, $name) use (&$etat, &$nom, &$http, &$var){
    if ($etat == 4) {
        $nom = trim($name);
        if ($http != '' and $nom != ""){
            $fichier = fopen('figurines.txt', 'a+');
            fputs($fichier, "\r\n");
            fputs($fichier, $http);
            fputs($fichier,"\r\n");
            fputs($fichier, $nom);
            fclose($fichier);
            $fichier = fopen('nb.txt', 'r+');
            fseek($fichier, 0);
            $var = $var + 1;
            fputs($fichier, $var);
            fclose($fichier);
            $bot->reply('La figurine a bien été ajouté.');
        }
        $etat = 0;
    }
    else if ($etat != 4){
        $bot->reply('Vous ne pouvez pas ajouter une nouvelle figurine pendant une partie.');
    }
});

$loop->run();