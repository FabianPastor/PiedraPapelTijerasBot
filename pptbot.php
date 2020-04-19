#!/usr/bin/env php
<?php
require_once("../classes/bot.class.php");
require_once("../classes/telegram.utils.class.php");
require_once("../classes/telegramcommand.class.php");

use Telegram\Utils as TGUtils;

$config = json_decode(file_get_contents("config.json"));

$bot = new botapi($config->token);


$bot->msg($config->root,"Me inicié!\n\n<pre>".TGUtils\pretty_print_html($bot->me)."</pre>","html");
$text = new TelegramCommand(true);

$texts["title"]   = "<b><i>Piedra Papel o Tijeras, saca lo que quieras...</i></b>\n";
$texts["ronda"]   = "     🏁 Ronda 🏁: ";
$texts["players"] = "Jugadores: ";
$texts["unknown"] = "<b>Juego No Disponible</b>";

$texts["piedra"] = "👊";
$texts["papel"]  = "✋";
$texts["tijera"] = "✌️";

$texts["voted"]   = "☑️";
$texts["waiting"] = "🕐";

$texts["sub_start"]   = "<b>Pulsa para agregarte al juego.</b>";
$texts["sub_next"]    = "<b>Siguiente Ronda</b>";
$texts["sub_playing"] = "<b>Empecemos a jugar, seleccionad una de las opciones de abajo</b>";
$texts["sub_select"]  = "<b>Seleccionad una opción.</b>";
$texts["sub_won"]     = "<b>Ganador: </b>";


$Game = [];
$running = true;
while($running){
  
  $updates = $bot->updates();
  if(empty($updates)) continue;
  if($updates->ok==false){
    echo "Updates Error: ".TGUtils\pretty_print_cli($updates).PHP_EOL;
    continue;
  }
  if(empty($updates->result)) continue;

  //echo "Updates: ".TGUtils\pretty_print_cli($updates).PHP_EOL;
  foreach($updates->result as $update){
    unset($M);
    echo json_encode($update).PHP_EOL.PHP_EOL;
    
    if(isset($update->message)){
      $M = $update->message;
      if(isset($M)){
        
        if(isset($M->text)){
          $text->set($M->text);
          
          switch($text->command){
            case "/stop":
              $running = false;
              $bot->msg($M->chat->id,"Stopping the bot.");
              $bot->updates($update->update_id+1);
              continue 3;
            break;
            
            case "/startppt":
              $bot->msg($M->chat->id, "Usa el modo inline escribiendo @{$bot->username} y un espacio.");
            //   $GameID = uniqid();
            //   $bot->msg(
            //     $M->chat->id, 
            //       "{$texts["title"]}\n".
            //       "{$texts["players"]}\n".
            //       "  <a href='tg:user?id={$M->from->id}'>{$M->from->first_name}</a>\n".
            //       "\n\n".
            //       $texts["sub_start"],
            //     "html",
            //     ' {"inline_keyboard":[[{
            //         "text":"Quiero Jugar!",
            //         "callback_data": "'.encodeData(["gamesid"=>$GameID, "command"=>2,"seleccion"=>1]).'"
            //       }]]}'
            //     );
            //   $Games[$GameID] = new stdClass;
            //   $Games[$GameID]->players[$M->from->id]=[
            //     "id"=>$M->from->id,
            //     "name"=>$M->from->first_name,
            //     "score"=>0,
            //     "voted"=>0
            //   ];
            //   $Games[$GameID]->round = 0;
            //   $Games[$GameID]->max_rounds = 3;
            //   $Games[$GameID]->started = time();
            //   unset($GameID);
            break;
          }
        }
      }
    }elseif(isset($update->callback_query)){
      echo "Update: ".TGUtils\pretty_print_cli($update).PHP_EOL;
      $C = $update->callback_query;
      
      if(isset($C->data)){
          
        $data = decodeData($C->data);
        $GameID = $data[0];
        
        
        if($data[1]==1 && isset($Games[$GameID])){
          $data[1]=2;
        }elseif($data[1]==1){
          $Games[$GameID] = new stdClass;
          $Games[$GameID]->players[$C->from->id]=[
            "id"=>$C->from->id,
            "name"=>$C->from->first_name,
            "score"=>0,
            "voted"=>0
          ];
          $Games[$GameID]->round = 0;
          $Games[$GameID]->max_rounds = 3;
          $Games[$GameID]->started = time();
          
          $Jugadores = "  ".user_link($Games[$GameID]->players[$C->from->id])."\n";
          $bot->editMessageText([
            "text"  =>  "{$texts["title"]}\n".
                        //"{$texts["ronda"]}{$Game->round}/{$Game->max_rounds}\n".
                        "{$texts["players"]}\n$Jugadores".
                        "\n\n".
                        $texts["sub_start"],
            "parse_mode"  => "html",
            "inline_message_id" => $C->inline_message_id,
            "reply_markup" => ' {"inline_keyboard":[[{
                "text":"Quiero Jugar!",
                "callback_data": "'.encodeData(["gamesid"=>$GameID, "command"=>2,"seleccion"=>1]).'"
              }]]}'
          ]);
          $bot->answerQuery($C->id);
          
        }
        
        if(!isset($Games[$GameID])){
          if(isset($C->inline_message_id)){
            $bot->editMessageText([
              "text"  => "{$texts["unknown"]}",
              "parse_mode"  => "html",
              "inline_message_id" => $C->inline_message_id,
            ]);
          }else{
            $bot->editMessageText([
              "text"  => "{$texts["unknown"]}",
              "parse_mode"  => "html",
              "chat_id"  => $C->message->chat->id,
              "message_id"  => $C->message->message_id
            ]);
            
          }
          continue;
        }
        $Game = &$Games[$GameID];
        
        
        switch($data[1]){
          
          // case "addplayer":
          // case "1":
          //   //FROMID
          // 
          // 
          // 
          // 
          //   $Game->players[]=["id"=>$C->from->id,"name"=>$C->from->first_name,"score"=>0];
          //   $Game->playersID[]=$C->from->id;
          // 
          //   $Jugadores = "";
          //   foreach($Game->players as $player){
          //     $Jugadores .= "<a href='tg://user?id={$player["id"]}'>{$player["name"]}</a>\n";
          //   }
          // 
          //   $bot->editMessageText([
          //     "text"  => "Juego Pidra Papel o Tijeras.\nJugadores:\n  $Jugadores\n\nPulsa para agregarte al juego.",
          //     "parse_mode"  => "html",
          //     "inline_message_id" => $C->inline_message_id,
          //     "reply_markup" => '{
          //       "inline_keyboard":[
          //         [
          //           {
          //             "text":"Quiero Jugar!",
          //             "data":"'.encodeData(["gamesid"=>$GameID,"command"=>2]).'"
          //           }
          //         ]
          //       ]
          //     }'
          //   ]);
          // break;
          
          case "startgame":
          case "2":
            if($data[2]==1){
              if(in_array($C->from->id,array_keys($Game->players))){
                $bot->answerQuery($C->id,"Ya estas jugando....",true);
                break;
              }
              
              $Game->players[$C->from->id]=[
                "id"=>$C->from->id,
                "name"=>$C->from->first_name,
                "score"=>0,
                "voted"=>0
              ];
              $Game->round=1;
            }elseif($data[2]==2){
              $Game->round++;
            }
            
            $Jugadores = "";
            foreach($Game->players as $id => $player){
              if($data[2]==2){
                $Game->players[$id]["voted"]=0;
                $player["voted"] = 0;
              }
              $voted = $player["voted"]>0?$texts["voted"]:$texts["waiting"];
              $Jugadores .= "  ".user_link($player)." $voted\n".
                            "    Ganados: {$player["score"]}\n";
            }
            
            $bot->editMessageText([
              "text"  =>  "{$texts["title"]}\n".
                          "{$texts["ronda"]}{$Game->round}/{$Game->max_rounds}\n".
                          "{$texts["players"]}\n$Jugadores".
                          "\n\n".
                          $texts["sub_playing"],
              "parse_mode"  => "html",
              "inline_message_id" => $C->inline_message_id,
              "reply_markup" => '{
                "inline_keyboard":[
                  [
                    {
                      "text":"'.$texts["piedra"].'",
                      "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>1]).'"
                    },
                    {
                      "text":"'.$texts["papel"].'",
                      "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>2]).'"
                    },
                    {
                      "text":"'.$texts["tijera"].'",
                      "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>3]).'"
                    }
                  ]
                ]
              }'
            ]);
            $bot->answerQuery($C->id);
          break;
          
          case "votaciones":
          case "3":
            if(!in_array($C->from->id,array_keys($Game->players))){
              $bot->answerQuery($C->id,"No estas participando...",true);
              break;
            }
            if($Game->players[$C->from->id]["voted"]>0){
              $bot->answerQuery($C->id,"Ya votaste...",true);
              break;
            }
            $Game->players[$C->from->id]["voted"] = $data[2];//STUFF;
            
            
            
            
            
            
            
            $allVoted=true;
            $Jugadores = "";
            foreach($Game->players as $id => $player){
              
              $voted = $player["voted"]>0?$texts["voted"]:$texts["waiting"];
              
              $allVoted = $allVoted && ($player["voted"]>0);
              $Jugadores .= "  ".user_link($player)." $voted\n    Ganados: {$player["score"]}\n";
            }
            
            if($allVoted){
              
              //TODO Logica del juego
              $players = [];
              foreach($Game->players as $player){
                $players[]=$player;
              }
              
              
              if(
                $players[0]["voted"] == $players[1]["voted"]
              ){
                $Game->max_rounds++;
              }elseif(
                $players[0]["voted"] == 1 && $players[1]["voted"] == 3 ||
                $players[0]["voted"] == 2 && $players[1]["voted"] == 1 ||
                $players[0]["voted"] == 3 && $players[1]["voted"] == 2
              ){
                $Game->players[$players[0]["id"]]["score"]++;
                
              }else{
                $Game->players[$players[1]["id"]]["score"]++;
              }
              
              
              
              
              $Ganado = false;
              $Jugadores = "";
              foreach($Game->players as $player){
                //TODO emojis
                $voted = $player["voted"]>0?$texts["voted"]:$texts["waiting"];
                switch($player["voted"]){
                  case "1":
                    $voted = $texts["piedra"];
                  break;
                  case "2":
                    $voted = $texts["papel"];
                  break;
                  case "3":
                    $voted = $texts["tijera"];
                  break;
                  
                }
                if($player["score"]>=2){
                  $Ganado = $player;
                }
                
                $Jugadores .= "  ".user_link($player)." $voted\n    Ganados: {$player["score"]}\n";
                
              }
              
              if($Ganado!==false){
                $bot->editMessageText([
                  "text"  =>  "{$texts["title"]}\n".
                              "{$texts["ronda"]}{$Game->round}/{$Game->max_rounds}\n".
                              "{$texts["players"]}\n$Jugadores".
                              "\n\n".
                              "  🏆 {$texts["sub_won"]}".user_link($Ganado)." 🏆" ,
                  "parse_mode"  => "html",
                  "inline_message_id" => $C->inline_message_id,
                ]);
              }else{
                $bot->editMessageText([
                  "text"  =>  "{$texts["title"]}\n".
                              "{$texts["ronda"]}{$Game->round}/{$Game->max_rounds}\n".
                              "{$texts["players"]}\n$Jugadores".
                              "\n\n".
                              $texts["sub_next"],
                  "parse_mode"  => "html",
                  "inline_message_id" => $C->inline_message_id,
                  "reply_markup" => '{
                    "inline_keyboard":[
                      [
                        {
                          "text":"Siguiente Ronda",
                          "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>2,"seleccion"=>2]).'"
                        }
                      ]
                    ]
                  }'
                ]);
              }
            }else{
              
              $bot->editMessageText([
                "text"  =>  "{$texts["title"]}\n".
                            "{$texts["ronda"]}{$Game->round}/{$Game->max_rounds}\n".
                            "{$texts["players"]}\n$Jugadores".
                            "\n\n".
                            $texts["sub_select"],
                "parse_mode"  => "html",
                "inline_message_id" => $C->inline_message_id,
                "reply_markup" => '{
                  "inline_keyboard":[
                    [
                      {
                        "text":"'.$texts["piedra"].'",
                        "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>1]).'"
                      },
                      {
                        "text":"'.$texts["papel"].'",
                        "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>2]).'"
                      },
                      {
                        "text":"'.$texts["tijera"].'",
                        "callback_data":"'.encodeData(["gamesid"=>$GameID,"command"=>3,"seleccion"=>3]).'"
                      }
                    ]
                  ]
                }'
              ]);
              
            }
            $bot->answerQuery($C->id);
          break;
          
        }
      }
    }elseif(isset($update->inline_query)){
      $Q = $update->inline_query;
      $GameID = uniqid();
      
      
      $restult = $bot->answerInlineQuery([
        "inline_query_id"     => $Q->id,
        "results"             =>  json_encode([[
                                    "type" => "article",
                                    "id" =>  $GameID,
                                    "title" =>  "Jugar a Piedra Papel o Tijeras",
                                    "input_message_content" => [
                                      "message_text" =>
                                        "{$texts["title"]}\n".
                                        "{$texts["players"]}\n".
                                        "\n\n".
                                        $texts["sub_start"],
                                      "parse_mode"=> "html"
                                    ],
                                    "reply_markup"=> ["inline_keyboard" => [[
                                        [
                                          "text"=>"Quiero Jugar!",
                                          "callback_data"=> encodeData(["gamesid"=>$GameID, "command"=>1,"seleccion"=>1])
                                        ]
                                      ]]]
                                  ]],128),
        "description"         => "Piedra Papel o Tijeras, saca lo que quieras...",
        "cache_time"          => 0,
        "is_personal"         => true,
        "next_offset"         =>  "",
        //"switch_pm_text"      => ,
        //"switch_pm_parameter" => ,
      ]);
      echo "Update: ".TGUtils\pretty_print_cli($update).PHP_EOL;
      echo "Result: ".TGUtils\pretty_print_cli($restult).PHP_EOL;
    }else{
      echo "Update: ".TGUtils\pretty_print_cli($update).PHP_EOL;
    }
      
    
  }
  
}
$bot->msg($config->root,"Me Morí!","html");

function user_link($player){
  return "<a href='tg://user?id={$player["id"]}'>{$player["name"]}</a>";
}
function encodeData($data = ""){
  $str = "" ;
  if(is_string($data)){
    $str = $data;
  }elseif(is_array($data) || is_object($data)){
    $items = [];
    foreach($data as $item){
      if(is_array($item) || is_object($item)){
        $items[] = json_encode($item);
      }else{
        $items[] = $item;
      }
    }
    $str = implode("|",$items);
  }
  return base64_encode($str);
}

function decodeData($encoded){
  $array = [];
  $str = base64_decode($encoded);
  $items = explode ("|",$str);
  foreach($items as $item){
    $obj = json_decode($item);
    if(isset($obj)){
      $array[] = $obj;
    }else{
      $array[] = $item;
    }
    
  }
  return $array;
}