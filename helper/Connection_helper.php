<?php
/**
 * Mengecek apakah printer Ethernet dapat dijangkau.
 *
 * @param string $address Format: "192.168.1.100" atau "192.168.1.100:9100"
 * @param int    $timeout Timeout dalam detik
 *
 * @return array
 * [
 *   'success' => bool,
 *   'ip'      => string,
 *   'port'    => int,
 *   'error'   => string|null
 * ]
 */

function checkNetworkPrinter(string $address, int $timeout = 3): array
{
    // Pisahkan IP dan port
    if (strpos($address, ':') !== false) {
        [$ip, $port] = explode(':', $address, 2);
        $port = (int) $port;
    } else {
        $ip = $address;
        $port = 9100; // Port default ESC/POS
    }

    // Validasi IP/hostname
    if (empty($ip)) {
        return [
            'success' => false,
            'ip'      => $ip,
            'port'    => $port,
            'error'   => 'Alamat printer kosong.'
        ];
    }
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        $errorMessage = match ($errno) {
            110 => 'Timeout: Printer tidak merespons.',
            111 => 'Connection refused: Port printer tertutup.',
            113 => 'No route to host: Printer tidak dapat dijangkau.',
            default => "[$errno] $errstr"
        };

        return [
            'success' => false,
            'ip'      => $ip,
            'port'    => $port,
            'error'   => $errorMessage
        ];
    }

    fclose($socket);
    return [
        'success' => true,
        'ip'      => $ip,
        'port'    => $port,
        'error'   => null
    ];
}

?>