<?php

return [
    // 图片地址
    'img_url'       =>      'http://admin.suiyiyou.net/image/',
    'app_url'       =>      'http://www.suiyiyou.net',                    //随意游主网站地址
    'app_id'        =>      'wx61c3a83a4a91e4ef',                           //微信公众号appid
    'app_secret'    =>      'f9f9de94ff1a6c6fa140b911d43b5e0e',           //秘钥
    'token'         =>      'suiyiyousyy',                                  //微信TOKEN

    // 缓存设置
    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // | 会话设置
    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'weixin',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // Cookie设置
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '.suiyiyou.net',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
];
