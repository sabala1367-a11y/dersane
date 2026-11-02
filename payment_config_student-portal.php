<?php
// Mipangilio ya malipo ya otomatiki. BADILISHA 'secret' mara moja na uipe benki/mtoa huduma wa malipo.
// Tengeneza secret mpya:  php -r 'echo bin2hex(random_bytes(32));'
return [
    'secret'      => 'BADILISHA-HII-NA-MSTARI-MREFU-WA-SIRI',
    // Kama mtoa huduma wa malipo ana IP zinazojulikana, ziandike hapa (mf. ['41.59.1.10']). Tupu = zote zinaruhusiwa.
    'allowed_ips' => [],
];
