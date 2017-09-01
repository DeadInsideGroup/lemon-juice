<?php

namespace Handler\Command;

use Telegram as B;

trait Command
{
    private function __command()
    {
        $__command_list = [
            "/start"  => ["!start", "~start"],
            "/time"   => ["!time", "~time"],
            "/ping"   => ["!ping", "~ping"],
            "/report" => ["!report", "~report"],
            "/kick"   => ["!kick", "~kick"],
            "/ban"    => ["!ban", "~ban"],
            "/unban"  => ["!unban", "~unban"],
            "/nowarn" => ["!nowarn", "~nowarn"],
            "/warn"   => ["!warn", "~nowarn"],
            "/help"   => ["!help", "~help"]
        ];
        $cmd = explode(" ", $this->lowertext, 2);
        $param = isset($cmd[1]) ? $cmd[1] : "";
        $cmd = explode("@", $cmd[0], 2);
        $cmd = $cmd[0];
        $flag = false;
        foreach ($__command_list as $key => $val) {
            if ($cmd == $key) {
                $r = $this->__do_command($key, $param);
                break;
            } else {
                foreach ($val as $vel) {
                    if ($cmd == $vel) {
                        $r = $this->__do_command($key, $param);
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    break;
                }
            }
        }
        return $r;
    }

    private function __do_command($command, $param = null)
    {
        switch ($command) {
        case '/start':
            return B::sendMessage(
                [
                        "text" => "Hai ".$this->actorcall.", ketik /help untuk menampilkan menu!",
                        "chat_id" => $this->chatid,
                        "reply_to_message_id" => $this->msgid,
                    ]
            );
                break;
        case '/help':
            return B::sendMessage([
                    "text" => "/time : Menampilkan waktu saat ini (Asia/Jakarta).",
                    "chat_id" => $this->chatid,
                    "reply_to_message_id" => $this->msgid
                ]);
                break;
        case '/time':
            return B::sendMessage([
                    "text" => date("Y-m-d H:i:s"),
                    "chat_id" => $this->chatid,
                    "reply_to_message_id" => $this->msgid
                ]);
                break;
        case '/ping':
            return B::sendMessage([
                    "text" => (time() - $this->event['message']['date'])." s",
                    "chat_id" => $this->chatid,
                    "reply_to_message_id" => $this->msgid
                ]);
            break;
        }
    }
}