<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 19:02
 */
return [
    -1  => '未知异常',
    0   => '请求成功',
    100 => '系统升级维护中',
    101 => '七牛文件资源不存在',
    102 => '视频标题过长(十六个字符以内)',
    103 => '视频资源已存在',
    104 => '视频不存在或已下架',
    105 => '账号已经在其它地方登陆',
    106 => '非法请求(没有登录)',
    107 => '登陆已失效,请重新登陆',
    108 => '手机号码错误',
    109 => '验证码发送过于频繁',
    110 => '验证码发送失败(联系客服)',
    111 => '手机号码已被注册',
    112 => '验证码错误或已过期',
    113 => '密码格式错误(6位至16位)',
    114 => '账号名或密码错误',
    115 => '视频评论不存在',
    116 => '评论长度不能超过300个字',
    117 =>'评论不能为空',
    118 => '不能重复点赞',
    119	=> '关注失败(之前已经关注)',
    120	=> '取消关注失败(之前没有关注)',
    121	=> '希望关注的用户不存在',
    122	=> '主题字数限于不少于1个且不多于16个字',
    123	=> '视频发表中@用户错误',
    124 => '视频发表中主题选择错误',
    125	=> '登录错误(ouath2配置错误 或者用户被禁用状态)',
    126	=> '视频发表中选择主题不能超过9个',
    127	 => '视频发表中@用户不能超过9个',
    128	=> '视频发表中视频长度不能大于30秒',
    129	=> '视频发表中选择的链接库资源不可用',
    130	=> '视频发表中视频长度不能小于10秒',
    131 => '视频发表中背景音乐不存在',
    132 => '视频发表中封面错误',
    133 => '视频发表中视频长度不能大于3分钟',
    134 => '用户不存在或者已禁用',
    135 => '参数不正确',

    151 => '申诉数据验证失败',
    152 => '申诉数据添加失败',
    153 => '视频当前状态不可提交申诉',
    154 => '视频状态修改失败',
    156 => '已经存在待审申诉',
    157 => '缺少视频id',
    158 => '非法视频id',
    159 => '缺少申诉内容',
    160 => '申诉内容合法长度1-255',
    161 => '昵称包含非法字符',
    162 => '昵称合法长度4-12字',
    163 => '昵称不能为空',
	164 => '该用户已被你拉黑',

    170=>'链接库增加失败',
    171=>'链接库视频增加失败',
    172=>'链接库资源已存在',
    173=>'链接库视频允许时长18秒至6分钟',
    174=>'非法类型',
    175=>'方向参数值非法',
    176=>'参赛作品不可以删除',
    177=>'链接库视频删除失败',
    178=>'无权限删除链接库视频',

    //音乐会相关
    500=>'无效的access_token',
    501=>'无效的用户id',
    502=>'重复记录',
    503=>'记录失败',
    504=>'取消失败',
    505=>'重复取消',
    506=>'用户没有报名记录',
    507=>'非法链接库视频id',
    508=>'参赛作品标题合法长度1-20字',
    509=>'参赛作品作曲合法长度1-10字',
    510=>'参赛作品作词合法长度1-10字',
    511=>'参赛作品选择失败',
    512=>'已经选择参赛作品',
    513=>'参赛作品选择失败(音乐会)',
    514=>'当前是正常状态',
    515=>'参赛作品时长要求18秒-6分钟',
    516=>'缺少参赛作品id',
    517=>'清除参赛作品失败',
    518=>'删除参赛选手失败',
    519=>'未找到用户报名记录',
    520=>'设置作品通过状态失败',

    //用户
    550 => '已经绑定手机号',
    551 => '手机号码已被使用',
    552 => '密码只能是字母和数字组合',
    553 => '用户昵称包含不允许的内容',
    554 => '昵称已存在',
    555 => '重复签到',
    556 => '已经绑定微信',
    557 => '微信信息错误',
    558 => '该微信已经绑定其它账号',
    559 => '已经绑定QQ',
    560 => 'QQ信息错误',
    561 => '该QQ已经绑定其它账号',
    562 => '已经绑定微博',
    563 => '微博信息错误',
    564 => '该微博已经绑定其它账号',
    565 => '无效的绑定类型',
    566 => '未绑定手机号',
    567 => '验证码输入错误',
    568 => '解绑失败',
    569 => '绑定失败',

    //黑名单
    600 => '不存在的用户',
    601 => '拉黑失败',
    602 => '解除失败',
    603 => '重复拉黑',
	604 => '黑名单上限为100个',

    //小视频
    650 => '删除失败',
    651 => '标题包含不允许的内容',
    652 => '主题包含不允许的内容',
    653 => '该视频已被删除',
    654 => '该视频违规被删除',
    655 => '原作者视频违规或被删除',
    656 => '视频正在审核中',
    657 => '视频审核未通过',

    //小视频评论
    700 => '评论包含不允许的内容',

    //系统消息
    750 => '参数类型错误',

    //推送设备token
    800 => '缺少设备类型',
    801 => '非法的设备类型',
    802 => '缺少设备token',

    //邀请码
    900 => '无效的邀请码',
    901 => '已经绑定过邀请码',
    902 => '绑定失败',
    903 => '请不要使用自己的邀请码哦',
    904 => '未绑定手机',

    //金币换股票
    1000 =>'缺少可接受的最大股票价格',
    1001 =>'缺少股票数量',
    1002 =>'实时股票结果大于最大可接受价格',
    1003 =>'金币余额不足以支付最大价格',
    1004 =>'今日购买数量超出限制',
    1005 =>'购买失败',
    1006 =>'休市期间,不能兑换股票',
    1007 =>'请稍后再试',
    1008 =>'余额不足',
    1009 =>'缺少支付密码',
    1010 =>'支付密码错误',
    1011 =>'兑换印象股前需要先绑定手机',
    1012 =>'兑换印象股前需要先设置支付密码',
    1013 =>'密码错误！请重新输入密码，您还可以输入两次',
    1014 =>'密码错误！请重新输入密码，您还可以输入一次',
    1015 =>'支付密码已锁定，请明天再试',
    1016 =>'扣除失败',

    //印象股
    1100 => '申请提现金额错误',
    1101 => '您有一笔提现申请正在审核中，需要审核通过后才能继续提现',
    1102 => '支付宝账号或者支付宝真实姓名错误',
    1103 => '未绑定微信',
    1104 => '可申请提现金币数量不足',
    1105 => '股票提现发生错误',
    1106 => '微信未绑定真实姓名',
    1107 => '该支付宝账号已经绑定其它账号',
    1108 => '缺少支付密码',
    1109 => '支付密码错误',
    1110 =>'提现前需要先绑定手机',
    1111 =>'提现前需要先设置支付密码',
    1112 =>'密码错误！请重新输入密码，您还可以输入两次',
    1113 =>'密码错误！请重新输入密码，您还可以输入一次',
    1114 =>'支付密码已锁定，请明天再试',
    1115 =>'首次提现金额不得低于1元',
    1116 =>'单次提现金额不得低于5元',
    1117 =>'申请提现金额错误',
    1118 =>'本月提现次数已达到上限',
    1119 =>'本月提现总额已达到上限',
    1120 =>'',
    1121 =>'有操作正在进行中，请稍后再试',



    //用户资料
    1200 => '不正确的性别',
    1201 => '签名格式不正确',
    1202 => '签名长度不可大于40字',
    1203 => '生日格式不正确',
    1204 => '不正确的城市',
    1205 => '头像格式不正确',
    1206 => '昵称不能为空',
    1207 => '昵称包含非法字符',
    1208 => '昵称合法长度4-12字',
    1209 => '昵称已存在',
    1210 => '没有修改',

	// 设置支付密码
	1300 => '登录密码不正确',
	1301 => '两次密码不一致',
	1302 => '请输入登入支付密码',
	1303 => '已设置支付密码',
	1304 => '请输入支付密码',
	1305 => '原密码不正确',
	1306 => '请输入验证码',
	1307 => '新密码与原密码一致',
	1308 => '密码由大写字母、小写字母、数字三种格式中的两种以上组合，长度6~16',
	1309 => '原手机号或密码错误',
	1310 => '支付密码错误',
	1311 => '新旧号码一致',
	1312 => '请输入登录密码',
	1313 => '修改失败',
	1314 => '没有设置支付密码',
	1315 => '手机号或密码错误',

    //意见反馈
    1400 => '图片错误',
    1401 => '内容错误',
    1402 => '内容字数不能超过200',
    1403 => '请输入吐槽内容',
    1404 => '吐槽太频繁了',
    1405 => '吐槽失败',

    //用户钱包
    1500 => '绑定失败',
    1501 => '缺少真实姓名',
    1502 => '真实姓名格式错误',
    1503 => '缺少账号',
    1504 => '账号格式错误',
    1505 => '未绑定微信账号',
    1506 => '已经绑定支付宝账号',
    1507 => '该支付宝账号已经绑定其它账号',
    1508 => '未绑定手机',
    1509 => '未设置支付密码',
    1510 => '解绑失败',
    1511 => '缺少验证码',
    1512 => '验证码格式错误',
    1513 => '解绑账号需要先绑定手机',
    1514 => '验证码错误',

    //用户身份证
    1600 => '缺少身份证照片',
    1601 => '图像识别失败,请重新上传',
    1602 => '信息已失效,请重新上传',
    1603 => '实名认证失败',
    1604 => '已经进行过实名认证',
    1605 => '身份证以被其它账号绑定',
    1606 => '提交次数太多',

    //授权用户
    1700 => '授权失败',
    1701 => '账号被禁用',
    1702 => '该QQ已经绑定其它账号',
    1703 => '该微信已经绑定其它账号',
    1704 => '该微博已经绑定其它账号',
    1710 => '已经绑定QQ',
    1711 => '已经绑定微信',
    1712 => '已经绑定微博',
    1733 => '绑定的微信与网页/小程序登陆微信不同',

    //登陆
    1800 => '请输入账号',
    1801 => '请输入密码',
    1802 => '账号不存在',
    1803 => '密码错误',
    1804 => '无权限',
    1805 => '无效的手机号码',
    1806 => '缺少验证码',
    1807 => '验证码错误',
    1808 => '账号被禁用',

    // PGC视频
    1900 => '页码错误',
    1901 => '每页显示数量错误',
    1902 => '排序错误',
    1903 => '排序字段错误',
    1904 => '筛选状态格式错误',
    1905 => '视频标题必填',
    1906 => '分类ID必填',
    1907 => '分类ID错误',
    1908 => '每页显示数量不能大于20',
    1909 => '失败',
    1910 => '标签不得超过三个',
    1911 => '非草稿状态无法编辑',

    //活动
    2000 =>'活动不存在'
];