<?php

namespace Tests\Feature\Reindexer\Fixture;

class HabrPost
{
    public const DATA = [
        [
            'link' => 'https://habr.com/en/post/448530/',
            'title' => 'Как Мегафон спалился на мобильных подписках',
            'img_src' => 'https://habrastorage.org/webt/-s/rv/0b/-srv0bzdyjzaux-ckre2-fphfcq.png',
            'user_nickname' => 'LMonoceros',
            'hubs' => [
                'Information Security',
                'Web services monetization',
                'Content-marketing'
            ],
            'rating' => 622
        ],
        [
            'link' => 'https://habr.com/en/post/454078/',
            'title' => '«Мобильный контент» бесплатно, без смс и регистраций. Подробности мошенничества от Мегафона',
            'img_src' => 'https://habrastorage.org/webt/po/lk/ak/polkakxd9xqe_kj9c-7zock6bck.png',
            'user_nickname' => 'LMonoceros',
            'hubs' => [
                'Legislation in IT',
                'Information Security',
                'Cellular communication',
            ],
            'rating' => 480
        ],
        [
            'link' => 'https://habr.com/en/post/451898/',
            'title' => 'Инновации по-русски',
            'img_src' => '',
            'user_nickname' => 'Vasyutka',
            'hubs' => [
                'Start-up development',
                'Legislation in IT',
                'IT career'
            ],
            'rating' => 447
        ],
        [
            'link' => 'https://habr.com/en/post/438514/',
            'title' => 'Как я год не работал в Сбербанке',
            'img_src' => '',
            'hubs' => [
                'Information Security',
                'IT-companies',
                'IT career'
            ],
            'rating' => 443
        ]
    ];
}
