<?php
final class HttpSocket
{
    static public function download($url, $bind_ip = false)
    { 
        $components = parse_url($url);
        if(!isset($components['query'])) $components['query'] = false;

        if(!$bind_ip) 
        {
            $bind_ip = $_SERVER['SERVER_ADDR'];
        }

        $header = array();
        $header[] = 'GET ' . $components['path'] . ($components['query'] ?  '?' . $components['query'] : '');
        $header[] = 'Host: ' . $components['host'];
        $header[] = 'User-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.7) Gecko/20100106 Ubuntu/9.10 (karmic) Firefox/3.5.7';
        $header[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $header[] = 'Accept-Language: en-us,en;q=0.5';
        $header[] = 'Accept-Encoding: gzip,deflate';
        $header[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
        $header[] = 'Keep-Alive: 300';
        $header[] = 'Connection: keep-alive';
        $header = implode("\n", $header) . "\n\n";
        $packet = $header;

        //----------------------------------------------------------------------
        // Connect to server
        //----------------------------------------------------------------------
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, $bind_ip);
        socket_connect($socket, $components['host'], 80);

        //----------------------------------------------------------------------
        // Send First Packet to Server
        //----------------------------------------------------------------------
        socket_write($socket, $packet);
        //----------------------------------------------------------------------
        // Receive First Packet to Server
        //----------------------------------------------------------------------
        $html = '';
        while(1) {
            socket_recv($socket, $packet, 4096, MSG_WAITALL);
            if(empty($packet)) break;
            $html .= $packet;
        }
        socket_close($socket);

        return $html;
    }
}
?>