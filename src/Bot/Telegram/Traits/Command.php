<?php

namespace Bot\Telegram\Traits;

use Sys\DB;
use Sys\Curl;
use Bot\Telegram\B;
use Bot\Telegram\Command\Warn;
use App\MyAnimeList\MyAnimeList;

trait Command
{   
    private function _report($args)
    {
        $args = trim($args);
        $r = json_decode(B::getChatAdministrators($this->room_id), 1) xor $i = 0;
        B::sendMessage("<i>Reported to ".count($r['result'])." admin(s)</i>", $this->room_id, $this->msg_id, ['parse_mode' => "HTML"]);
        if (isset($this->input['message']['chat']['username'])) {
            $room = "<a href=\"https://telegram.me/".$this->input['message']['chat']['username']."\">".$this->input['message']['chat']['title']."</a>";
            $op = ['parse_mode'=>'HTML', 'disable_web_page_preview'=>true, "reply_markup"=>json_encode(["inline_keyboard"=>[[["text"=>"Go to the message","url"=> "https://telegram.me/".$this->input['message']['chat']['username']."/".$this->msg_id]]]])];
        } else {
            $room = "<b>".$this->input['message']['chat']['title']."</b>";
            $op = ['parse_mode'=>'HTML', 'disable_web_page_preview'=>true];
        }
        $reporter = isset($this->uname) ? "<a href=\"https://telegram.me/".$this->uname."\">".$this->actor."</a>" : "<code>".$this->actor."</code>";
        var_dump("Laporan dari grup <b>".$room."</b> oleh ".$reporter.(!empty($args) ? "\n\n<pre>".htmlspecialchars($args)."</pre>" : ""));
        foreach($r['result'] as $a) {
            B::sendMessage("Laporan dari grup <b>".$room."</b> oleh ".$reporter.(!empty($args) ? "\n\n<pre>".htmlspecialchars($args)."</pre>" : ""), $a['user']['id'], null, $op);
        }
    }

    private function _save($args)
    {
        $args = explode(" ", trim(str_replace("\n", " ", $args)), 2);
        if (isset($this->input['message']['reply_to_message'])) {
            if (isset($this->input['message']['reply_to_message']['photo'])) {
                $sb = json_decode(B::sendMessage("Downloading your image...", $this->room_id, $this->input['message']['reply_to_message']['message_id']), true);
                is_dir(IMG_ASSETS) or print shell_exec("mkdir -p ".IMG_ASSETS);
                $p = end($this->input['message']['reply_to_message']['photo']);
                $p = json_decode(B::getFile($p['file_id']),true);
                $st = new Curl("https://api.telegram.org/file/bot".TOKEN."/".$p['result']['file_path']);
                $file = $st->exec();
                $handle = fopen(IMG_ASSETS."/".($fname = sha1($file)).".jpg", "w");
                fwrite($handle,  $file);
                fclose($handle);
                $exe = DB::table("assets")->insert([
                        "id" => null,
                        "title" => $args[0],
                        "caption" => (isset($args[1]) ? $args[1] : null),
                        "file_name" => $fname,
                        "type" => "image",
                        "created_at" => (date("Y-m-d H:i:s"))
                    ]);
                if ($exe) {
                    B::editMessageText(
                        [
                            "text"=>"Media ini telah disimpan dengan judul <code>".htmlspecialchars($args[0])."</code>",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id'],
                            "reply_markup"=>json_encode(["inline_keyboard"=>[[["text"=>"Buka file","url"=>ASSETS_URL."/images/".$fname.".jpg"]]]])
                        ]
                    );
                } else {
                    B::editMessageText(
                        [
                            "text"=>"Gagal menyimpan media !",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id']
                        ]
                    );
                }
                
            } elseif (isset($this->input['message']['reply_to_message']['document'])) {
                $sb = json_decode(B::sendMessage("Downloading your file...", $this->room_id, $this->input['message']['reply_to_message']['message_id']), true);
                $p = json_decode(B::getFile($this->input['message']['reply_to_message']['document']['file_id']), true);
                $st = new Curl("https://api.telegram.org/file/bot".TOKEN."/".$p['result']['file_path']);
                $ex = explode(".", $p['result']['file_path']);
                $st = $st->exec();
                is_dir(ASSETS_R) or shell_exec("mkdir -p ".ASSETS_R);
                $handle = fopen(ASSETS_R."/".($fname = sha1($st)).".".end($ex), "w");
                fwrite($handle, $st);
                fclose($handle);
                 $exe = DB::table("assets")->insert([
                        "id" => null,
                        "title" => $args[0],
                        "caption" => (isset($args[1]) ? $args[1] : null),
                        "file_name" => $fname,
                        "type" => "file",
                        "created_at" => (date("Y-m-d H:i:s"))
                    ]);
                if ($exe) {
                    B::editMessageText(
                        [
                            "text"=>"Media ini telah disimpan dengan judul <code>".htmlspecialchars($args[0])."</code>",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id'],
                            "reply_markup"=>json_encode(["inline_keyboard"=>[[["text"=>"Buka file","url"=>ASSETS_URL."/files/".$fname.".".end($ex)]]]])
                        ]
                    );
                } else {
                    B::editMessageText(
                        [
                            "text"=>"Gagal menyimpan media !",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id']
                        ]
                    );
                }
            } elseif (isset($this->input['message']['reply_to_message']['video'])) {
                $sb = json_decode(B::sendMessage("Downloading your video...", $this->room_id, $this->input['message']['reply_to_message']['message_id']), true);
                $p = json_decode(B::getFile($this->input['message']['reply_to_message']['video']['file_id']), true);
                $st = new Curl("https://api.telegram.org/file/bot".TOKEN."/".$p['result']['file_path']);
                $ex = explode(".", $p['result']['file_path']);
                $st = $st->exec();
                is_dir(VID_ASSETS) or shell_exec("mkdir -p ".VID_ASSETS);
                $handle = fopen(VID_ASSETS."/".($fname = sha1($st)).".".end($ex), "w");
                fwrite($handle, $st);
                fclose($handle);
                 $exe = DB::table("assets")->insert([
                        "id" => null,
                        "title" => $args[0],
                        "caption" => (isset($args[1]) ? $args[1] : null),
                        "file_name" => $fname,
                        "type" => "video",
                        "created_at" => (date("Y-m-d H:i:s"))
                    ]);
                if ($exe) {
                    B::editMessageText(
                        [
                            "text"=>"Media ini telah disimpan dengan judul <code>".htmlspecialchars($args[0])."</code>",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id'],
                            "reply_markup"=>json_encode(["inline_keyboard"=>[[["text"=>"Buka file","url"=>ASSETS_URL."/videos/".$fname.".".end($ex)]]]])
                        ]
                    );
                } else {
                    B::editMessageText(
                        [
                            "text"=>"Gagal menyimpan media !",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id']
                        ]
                    );
                }
            } elseif (isset($this->input['message']['reply_to_message']['sticker'])) {
                $sb = json_decode(B::sendMessage("Downloading your sticker...", $this->room_id, $this->input['message']['reply_to_message']['message_id']), true);
                $p = json_decode(B::getFile($this->input['message']['reply_to_message']['sticker']['thumb']['file_id']), true);
                $st = new Curl("https://api.telegram.org/file/bot".TOKEN."/".$p['result']['file_path']);
                $ex = explode(".", $p['result']['file_path']);
                $st = $st->exec();
                is_dir(ASSETS_R) or shell_exec("mkdir -p ".ASSETS_R);
                $handle = fopen(ASSETS_R."/".($fname = sha1($st)).".png", "w");
                fwrite($handle, $st);
                fclose($handle);
                 $exe = DB::table("assets")->insert([
                        "id" => null,
                        "title" => $args[0],
                        "caption" => (isset($args[1]) ? $args[1] : null),
                        "file_name" => $fname,
                        "type" => "sticker",
                        "created_at" => (date("Y-m-d H:i:s"))
                    ]);
                if ($exe) {
                    B::editMessageText(
                        [
                            "text"=>"Media ini telah disimpan dengan judul <code>".htmlspecialchars($args[0])."</code>",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id'],
                            "reply_markup"=>json_encode(["inline_keyboard"=>[[["text"=>"Buka file","url"=>ASSETS_URL."/files/".$fname.".png"]]]])
                        ]
                    );
                } else {
                    B::editMessageText(
                        [
                            "text"=>"Gagal menyimpan media !",
                            "parse_mode" => "HTML",
                            "disable_web_page_preview" => true,
                            "chat_id" => $this->room_id,
                            "message_id" => $sb['result']['message_id']
                        ]
                    );
                }
            }
        }
    }

    private function _warn($args)
    {
    	$args = trim($args);
    	if ($this->chat_type != "private") {
    		if (isset($this->input['message']['reply_to_message']['from']['id']) and strpos(B::getChatAdministrators($this->room_id), $this->user_id)) {

    			$st = new Warn([
    					"uifd" => $this->input['message']['reply_to_message']['from']['id']."|".$this->room_id,
    					"userid" => $this->input['message']['reply_to_message']['from']['id'],
    					"reason" => $args,
    					"room_id" => $this->room_id,
    					"warner" => $this->user_id,
    					"msg_id" => $this->msg_id,
    					"username" => $this->input['message']['reply_to_message']['from']['username'],
    					"actor" => ($this->input['message']['reply_to_message']['from']['first_name']. (isset($this->input['message']['reply_to_message']['from']['last_name']) ? " ".$this->input['message']['reply_to_message']['from']['last_name'] : ""))
    				]);
    			$st->run();
    		} else {
    			B::deleteMessage([
    					"chat_id" => $this->room_id,
    					"message_id" => $this->msg_id
    				]);
    		}
    	}
    }

    private function _ban($args)
    {
        $args = trim($args);
        if ($this->chat_type != "private") {
            if (isset($this->input['message']['reply_to_message']['from']['id']) and strpos(B::getChatAdministrators($this->room_id), $this->user_id)) {
                $a = B::restrictChatMember([
                        "chat_id" => $this->room_id,
                        "user_id" => $this->input['message']['reply_to_message']['from']['id']
                    ]);
                $b = B::kickChatMember($this->room_id, $this->input['message']['reply_to_message']['from']['id']);
                if ($a == '{"ok":true,"result":true}' or $b == '{"ok":true,"result":true}') {
                    if (isset($this->uname)) {
                        $user = "<a href=\"https://telegram.me/".$this->uname."\">".$this->actor_call."</a> banned ";
                    } else {
                        $user = $this->actor_call." banned ";
                    }
                    if (isset($this->input['message']['reply_to_message']['from']['username'])) {
                        $user .= "<a href=\"https://telegram.me/".$this->input['message']['reply_to_message']['from']['username']."\">".$this->input['message']['reply_to_message']['from']['first_name']."</a> !";
                    } else {
                        $user .= $this->input['message']['reply_to_message']['from']['first_name'];
                    }
                    B::sendMessage($user, $this->room_id, null, ["parse_mode"=>"HTML", 'disable_web_page_preview'=>true]);
                } else {
                    B::sendMessage($a."\n".$b, $this->room_id, $this->msg_id);
                }
            } else {
                B::deleteMessage([
                        "chat_id" => $this->room_id,
                        "message_id" => $this->msg_id
                    ]);
            }
        }
    }

    private function _idan($args)
    {
        $args = trim($args);
        if (!empty($args)) {
            $fx = function ($str) {
                if (is_array($str)) {
                    return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode(implode($str))));
                }
                return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
            };
            $st = new MyAnimeList(MAL_USER, MAL_PASS);
            $st = $st->get_info($args);
            $st = isset($st['entry']) ? $st['entry'] : $st;
            if (is_array($st) and count($st)) {
                $img = $st['image'];
                unset($st['image']);
                $rep = "";
                foreach ($st as $key => $value) {
                    $ve = $fx($value);
                    !empty($ve) and $rep .= "<b>".ucwords($key)."</b> : ".($ve)."\n";
                }
                B::sendPhoto($img, $this->room_id, null, $this->msg_id);
                B::sendMessage(str_replace("\n\n", "\n", $rep), $this->room_id, null, ["parse_mode"=>"HTML"]);
            } else {
                B::sendMessage("Mohon maaf, anime \"{$args}\" tidak ditemukan !", $this->room_id, $this->msg_id);
            }
        } else {
            B::sendMessage("Sebutkan ID Anime yang ingin kamu cari !", $this->room_id, $this->msg_id, ["reply_markup" => json_encode(["force_reply"=>true, "selective"=>true])]);
        }
    }

    private function _anime($args)
    {
        if (!empty($args)) {
            $st = new MyAnimeList(MAL_USER, MAL_PASS);
            $st->search($args);
            $st->exec();
            $st = $st->get_result();
            if (isset($st['entry']['id'])) {
                B::sendMessage("Hasil pencarian anime :\n<b>{$st['entry']['id']}</b> : {$st['entry']['title']}\n\nBerikut ini adalah anime yang cocok dengan <b>{$args}</b>.\n\nKetik /idan [spasi] [id_anime] atau balas dengan id anime untuk menampilkan info anime lebih lengkap.", $this->room_id, $this->msg_id, ["parse_mode"=>"HTML","reply_markup" => json_encode(["force_reply"=>true, "selective"=>true])]);
            } elseif (is_array($st) and $xz = count($st['entry'])) {
                $rep = "Hasil pencarian anime :\n";
                foreach ($st['entry'] as $vz) {
                    $rep .= "<b>".$vz['id']."</b> : ".$vz['title']."\n";
                }
                B::sendMessage($rep."\nBerikut ini adalah beberapa anime yang cocok dengan <b>{$args}</b>.\n\nKetik /idan [spasi] [id_anime] atau balas dengan id anime untuk menampilkan info anime lebih lengkap.", $this->room_id, $this->msg_id, ["parse_mode" => "HTML", "reply_markup" => json_encode(["force_reply"=>true, "selective"=>true])]);
            } else {
                B::sendMessage("Mohon maaf, anime \"{$args}\" tidak ditemukan !", $this->room_id, $this->msg_id);
            }
        } else {
            $a = B::sendMessage("Anime apa yang ingin kamu cari? ~", $this->room_id, $this->msg_id, ["reply_markup"=>json_encode(["force_reply"=>true, "selective"=>true])]);
        }
    }

    private function _qanime($args)
    {
        if (empty($args)) {
            B::sendMessage("Anime apa yang ingin kamu cari?", $this->room_id, $this->msg_id);
        } else {
            $fx = function ($str) {
                if (is_array($str)) {
                    return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode(implode($str))));
                }
                return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
            };
            $st = (new MyAnimeList(MAL_USER, MAL_PASS))->simple_search($args);
            if (is_array($st) and count($st)) {
                $img = $st['image'];
                unset($st['image']);
                $rep = "";
                foreach ($st as $key => $value) {
                    $ve = $fx($value);
                    !empty($ve) and $rep .= "<b>".ucwords($key)."</b> : ".($ve)."\n";
                }
                B::sendPhoto($img, $this->room_id, null, $this->msg_id);
                B::sendMessage(str_replace("\n\n", "\n", $rep), $this->room_id, null, ["parse_mode" => "HTML"]);
            } else {
                B::sendMessage("Mohon maaf, anime \"{$args}\" tidak ditemukan !", $this->room_id);
            }
        }
    }

    private function _idma($args)
    {
        $args = trim($args);
        if (!empty($args)) {
            $fx = function ($str) {
                if (is_array($str)) {
                    return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode(implode($str))));
                }
                return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
            };
            $st = new MyAnimeList(MAL_USER, MAL_PASS);
            $st = $st->get_info($args, "manga");
            $st = isset($st['entry']) ? $st['entry'] : $st;
            if (is_array($st) and count($st)) {
                $img = $st['image'];
                unset($st['image']);
                $rep = "";
                foreach ($st as $key => $value) {
                    $ve = $fx($value);
                    !empty($ve) and $rep .= "<b>".ucwords($key)."</b> : ".($ve)."\n";
                }
                B::sendPhoto($img, $this->room_id, null, $this->msg_id);
                B::sendMessage(str_replace("\n\n", "\n", $rep), $this->room_id, $this->msg_id, ["parse_mode"=>"HTML"]);
            } else {
                B::sendMessage("Mohon maaf, manga \"{$args}\" tidak ditemukan !", $this->room_id, $this->msg_id);
            }
        } else {
            B::sendMessage("Sebutkan ID Manga yang ingin kamu cari !", $this->room_id, $this->msg_id, ["reply_markup"=>json_encode(["force_reply"=>true, "selective"=>true])]);
        }
    }

    private function _manga($args)
    {
        $args = trim($args);
        if (!empty($args)) {
            $st = new MyAnimeList(MAL_USER, MAL_PASS);
            $st->search($args, "manga");
            $st->exec();
            $st = $st->get_result();
            if (isset($st['entry']['id'])) {
                $rep = "";
                B::sendMessage("Hasil pencarian manga :\n<b>{$st['entry']['id']}</b> : {$st['entry']['title']}\n\nBerikut ini adalah manga yang cocok dengan <b>{$args}</b>.\n\nKetik /idma [spasi] [id_anime] atau balas dengan id manga untuk menampilkan info manga lebih lengkap.", $this->room_id, $this->msg_id, ["parse_mode"=>"HTML", "reply_markup"=>json_encode(["force_reply"=>true,"selective"=>true])]);
            } elseif (is_array($st) and $xz = count($st['entry'])) {
                $rep = "Hasil pencarian manga :\n";
                foreach ($st['entry'] as $vz) {
                    $rep .= "<b>".$vz['id']."</b> : ".$vz['title']."\n";
                }
                B::sendMessage($rep."\nBerikut ini adalah beberapa manga yang cocok dengan <b>{$args}</b>.\n\nKetik /idma [spasi] [id_manga] atau balas dengan id manga untuk menampilkan info manga lebih lengkap.", $this->room_id, $this->msg_id, ["parse_mode"=>"HTML", "reply_markup"=>json_encode(["force_reply"=>true, "selective"=>true])]);
            } else {
                B::sendMessage("Mohon maaf, manga \"{$args}\" tidak ditemukan !", $this->room_id, $this->msg_id);
            }
        } else {
            B::sendMessage("Manga apa yang ingin kamu cari? ~", $this->room_id, $this->msg_id, ["reply_markup"=>json_encode(["force_reply"=>true, "selective"=>true])]);
        }
    }

    private function _qmanga($args)
    {
        $args = trim($args);
        if (!empty($args)) {
            $fx = function ($str) {
                if (is_array($str)) {
                    return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode(implode($str))));
                }
                return trim(str_replace(array("[i]", "[/i]","<br />"), array("<i>", "</i>","\n"), html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
            };
            $st = (new MyAnimeList(MAL_USER, MAL_PASS))->simple_search($args, "manga");
            if (is_array($st) and count($st)) {
                $img = $st['image'];
                unset($st['image']);
                $rep = "";
                foreach ($st as $key => $value) {
                    $ve = $fx($value);
                    !empty($ve) and $rep .= "<b>".ucwords($key)."</b> : ".($ve)."\n";
                }
                B::sendPhoto($img, $this->room_id, $this->msg_id);
                B::sendMessage(str_replace("\n\n", "\n", $rep), $this->room_id, null, ["parse_mode"=>"HTML"]);
            } else {
                B::sendMessage("Mohon maaf, manga \"{$args}\" tidak ditemukan !", $this->room_id, $this->msg_id);
            }
        } else {
            B::sendMessage("Manga apa yang ingin kamu cari?", $this->room_id, $this->msg_id, ["reply_markup"=>json_encode(["force_reply"=>true, "selective"=>true])]);
        }
    }
}
