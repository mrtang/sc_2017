<?php
    return [
        'API_POST_NL'    => 'https://www.nganluong.vn/checkout.php',
        'WS_NGANLUONG'   => 'https://www.nganluong.vn/paygate.shipchung.php?wsdl',


        'ALEPAY_API'            => 'https://alepay.vn/checkout/v1/',
        'ALEPAY_API_KEY'        => 'iH4gX5efdTNaNjAI7omblxkNafs7sD',
        'ALEPAY_ENCRYPT_KEY'    => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCFV4jQiKM+OuaPIXWB51ut8FIZKb9OCkUuMp2vbWYBk58Gg2seh/XhnXgiCQR9NCqKyA0Rx/nJtSqH98/69zzjz0wtBlefYDnZQnPsAgz+HsLt7SuKkfbf56uduZgUOX+iZyx19b7tyNqym+y7EGcy6+tQwJ6Ki+pRia+IWk0/iQIDAQAB',
        'ALEPAY_CHECKSUM_KEY'   => 'VgqWPtLkO92jEJOxBBenrV7X3j0Iwh',

        'ALEPAY_BM_API_KEY'        => 'sEDN9CJHm18IQWOm3y5IYpd6r9YalV',
        'ALEPAY_BM_ENCRYPT_KEY'    => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQChG7m3EpAO7kQN6zPxm6OVLGhIL/fh+csOR0kmLMlbyqUXzbX/08ej2SzdtleVTQvL030otD2BI4Y7uAxmv1q+pcedQ3g9JjSLpGymlMAtH0k/h+t2SEnVnp1NH29smMvMLd9bzzDHt93tXXAM3QgwioOz3oJ8+KG2QFcO/bGdzwIDAQAB',
        'ALEPAY_BM_CHECKSUM_KEY'   => 'gfrr7EvsfXENSEmXus0NpGMUGug2qq',
        
        // Login Journey Api ShipChung
        'cfg_carrier_api' => array(
            'vtp'       => 'vtp1k5xf94y8',
            'ghn'       => 'ghn1k4x1h3y8',
            'ghtk'      => '30bdc9c64878eba7821ee6acd613c789',
            'ems'       => '4c2afd9689d20d8cbe01dc682a58cd91',
            'vnp'       => '43fd530de60f845f4dfe6cc75a8a999c',
            'netco'     => 'effcd81f167150cbc2da9e073f7a2b51',
            '123giao'   => '0a7c1ac2659c5136e2d19505a1202181',
            'ttc'       => '3dbe36d787fe40179d28f62e766e86d5',
            'gts'       => 'bba2174fcffcdab7f7092c2fd9c23bcf',
            'ws'        => '29ee1d5c776a4e73967cea2184fc2ce6',
            'njv'       => 'c5c688235c628707023df94677935909',
            'test'      => '15c688235c628707023df94677935902'
            
        ),
        
        'cfg_carrier_ip' => [
            'vtp'       => [],
            'ghn'       => [],
            'ghtk'      => [],
            'ems'       => [],
            'vnp'       => [],
            'netco'     => [],
            '123giao'   => [],
            'ttc'       => [],
            'gts'       => [],
            'ws'        => [],
            'njv'       => [],
            'test'      => ['123.30.40.12', '14.177.64.231'] 

            

        ],
         
        // Login Journey Api ShipChung
        'cfg_old_carrier' => array(
                                1 => 'vtp',
                                2 => 'vnp',
                                3 => 'ghn',
                                4 => '123giao',
                                5 => 'netco',
                                6 => 'ghtk',
                                8 => 'ems',
                                9 => 'gold',
                            ),

        'domain'        => [
            'boxme'         =>  [
                'create'        => '6cc590e85e16dbcfc2d4b7037fc8ca45',
                'caculate'      => '9604454a2c45743acbe0a92db6ef18d4',
                'accounting'    => '9e2beff1d29179dc4bcead2e550f57d5',
                'seller'        => 'de5cf2469eba6b5d8b5499fad04b85f3',
                'ops'           => 'a327f7b8bb4d6770993dbf48f745c021'
            ]
        ],
        'connection'    => ['10.0.0.81','10.0.0.88']
    ];
?>