<?php
return [
    //ES
    'elastic_search'=>[
        'hosts'=>[
            [
                'host' => 'es-cn-4590sfqdt0002n8a9.elasticsearch.aliyuncs.com',
                'port' => '9200',
                'scheme' => 'http',
                'user' => 'elastic',
                'pass' => 'BvpwccQsJic4NCfj'
            ],
        ],
        'indices'=>[
            'user'=>'user_test',
            'video'=>'video_test',
        ],
    ],

    //云市场
    'market'=>[
        'app_code'=>'c91f8a068d0249d1bb0540bb3d1c97a1',
    ],

    //短信
    'sms'=>[
        'access_key' => 'LTAIUhnXx92AdfBT',
        'access_secret' => 'IseOzJdK0gi5S5HT9vnlUnKTZP8BlS',
        'sign_name' => '小印象',
    ],

    //消息服务
    'message_service'=>[
        'access_key' => 'LTAIUhnXx92AdfBT',
        'access_secret' => 'IseOzJdK0gi5S5HT9vnlUnKTZP8BlS',
        'end_point' => 'http://1133066415929939.mns.cn-shenzhen.aliyuncs.com/',
        'queue' => [
            'test1',
            'test2',
        ],
    ],
];