-- tinyint     0~127
-- smallint    0~32767
-- int         0~2187483647
-- bigint
-- engine=InnoDB  or  engine=MyISAM

-- md5 len = 32

--\ 表名前缀说明
-- u: 用户相关的表
-- o: 订单相关的表
-- s: 系统全局的表

-----------------------------------用户相关-----------------------------------
-- 用户基本信息表
drop table if exists u_user;
create table u_user (
    id                  int unsigned not null auto_increment,

    phone               char(11) not null default '',
    passwd              char(32) not null default '',

    nickname            varchar(255) not null default '',
    sex                 tinyint not null default 0,                 # 性别 1:男 2:女 0:未知
    headimgurl          varchar(255) not null default '',           # 用户头像

    service_user_id     int unsigned not null default 0,            # 服务人ID

    cash_amount         decimal(10,2) not null default 0.0,         # 现金余额

    state               tinyint not null default 0,                 # 用户状态

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`id`),
    index idx_phone(`phone`),
    index idx_nickname(`nickname`)
)engine=InnoDB default charset=utf8;

-- 用户详情表
drop table if exists u_user_detail;
create table u_user_detail (
    user_id             int unsigned not null default 0,

    score_amount        decimal(10,2) not null default 0.0,         # 积分数量

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`user_id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8;

-- 微信用户信息表
drop table if exists u_wx_user;
create table u_wx_user (
    id                  int unsigned not null auto_increment,

    user_id             int unsigned not null default 0,
    openid              varchar(63) not null default '',
    nickname            varchar(255) not null default '',
    sex                 tinyint not null default 0,                 # 性别 1:男 2:女 0:未知
    headimgurl          varchar(255) not null default '',           # 用户头像
    province            varchar(60) not null default '',            # 省
    city                varchar(60) not null default '',            # 市
    subscribe           tinyint not null default 0,                 # 是否关注 0/1
    subscribe_time      int not null default 0,                     # 关注时间(取最后一次关注)
    subscribe_from      tinyint not null default 0,                 # 关注方式(仅记首次) 1:已经关注
                                                                    # 2:普通关注 3:场景二维码
    unionid             varchar(63) not null default '',            # 腾讯平台唯一ID
    lng                 decimal(12,8) not null default '0.0',       # 经度180.12345678
    lat                 decimal(12,8) not null default '0.0',       # 纬度180.12345678

    state               tinyint not null default 0,                 # 用户状态

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间
    atime               int not null default 0,                     # 与公众号交互时间

    primary key (`id`),
    index idx_user_id(`user_id`),
    unique key key_openid(`openid`)
)engine=InnoDB default charset=utf8;

-- 用户地址表
drop table if exists u_user_address;
create table u_user_address (
    id                  int unsigned not null auto_increment,

    user_id             int unsigned not null default 0,

    re_name             varchar(31) not null default '',            # 收件人姓名
    re_phone            char(11) not null default '',               # 收件人手机号
    addr_type           tinyint not null default 0,                 # 地址类型 0:未知 1:公司 2:家庭

    province_id         int not null default 0,                     # 省
    city_id             int not null default 0,                     # 市
    district_id         int not null default 0,                     # 区
    detail              varchar(255) not null default '',           # 详细街道地址
    re_id_card          varchar(18) not null default '',            # 收件人身份证

    is_default          tinyint not null default 0,                 # 是否为默认地址 0/1

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8;

-----------------------------------商城交易相关-----------------------------------
-- 订单基础表
drop table if exists u_order;
create table u_order (
    id                  int unsigned not null auto_increment,

    order_id            char(16) not null default '',               # 01 + 150223 + 492933 + 32
    user_id             int unsigned not null default 0,

    -- 收货信息
    re_name             varchar(31) not null default '',            # 收件人姓名
    re_phone            char(11) not null default '',               # 收件人手机号
    addr_type           tinyint not null default 0,                 # 地址类型 0:未知 1:公司 2:家庭
    province_id         int not null default 0,                     # 省
    city_id             int not null default 0,                     # 市
    district_id         int not null default 0,                     # 区
    detail              varchar(255) not null default '',           # 详细街道地址
    re_id_card          varchar(18) not null default '',            # 收件人身份证

    -- 状态
    pay_state           tinyint not null default 0,                 # 0:未支付 1:支付中 2:支付成功
    order_state         tinyint not null default 0,                 # 0:创建 1:完成 3:取消 4:超时

    total_amount        decimal(10,2) not null default 0.0,         # 订单总金额
    ol_pay_amount       decimal(10,2) not null default 0.0,         # 在线支付金额
    ac_pay_amount       decimal(10,2) not null default 0.0,         # 账户支付金额
    ol_pay_type         tinyint not null default 0,                 # 在线支付方式 0:非在线支付
                                                                    # 1:微信 2:支付宝 3:银联
    postage             decimal(10,2) not null default 0.0,         # 邮费

    remark              varchar(255) not null default '',           # 客户备注信息

    attach              varchar(255) not null default '',           # json格式附属信息

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间
    m_user              varchar(31) not null default '',            # 修改人

    primary key (`id`),
    unique key order_id(`order_id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8;

-- 订单商品表
drop table if exists u_order_goods
create table u_order_goods (
    id                  int unsigned not null auto_increment,

    order_id            char(16) not null default '',

    -- 商品快照
    goods_id            int unsigned not null default 0,            # 商品ID
    sku_info            varchar(255) not null default '',           # sku 信息json格式{'id':[1,2], 'val':[33,34]}
    amount              int unsigned not null default 0,            # 商品数量
    price               decimal(10,2) not null default 0.0,         # 商品价格

    state               tinyint not null default 0,                 # 0:待发货   1:已出库   2:已发货 3:已收货
                                                                    # 4:申请退货 5:退货成功 6:退货失败
                                                                    # 7:申请换货 8:换货成功 9:换货失败
    commented           tinyint not null default 0,                 # 是否评论过 

    attach              varchar(255) not null default '',           # 订单商品附属信息(json)

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间
    m_user              varchar(31) not null default '',            # 修改人

    primary key (`id`),
    index idx_order_id(`order_id`)
}engine=InnoDB default charset=utf8;

-- 购物车表
drop table if exists u_cart
create table u_cart (
    id                  int unsigned not null auto_increment,

    user_id             int unsigned not null default 0,

    goods_id            int unsigned not null default 0,            # 商品ID
    sku_info            varchar(255) not null default '',           # sku 信息json格式{'id':[1,2], 'val':[33,34]}
    amount              int unsigned not null default 0,            # 商品数量

    attach              varchar(255) not null default '',           # 购物车商品附属信息(json)

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8;

-----------------------------------商品相关表-----------------------------------
-- 商品表
drop table if exists g_goods
create table g_goods (
    id                  int unsigned not null auto_increment,

    goods_id            int unsigned not null default 0,            # 商品ID
    supplier_id         int unsigned not null default 0,            # 供应商编号
    name                varchar(127) not null default '',           # 商品名
    category_id         int unsigned not null default 0,            # 商品类别ID
    brand_id            int unsigned not null default 0,            # 商品牌ID
    market_price        decimal(10,2) not null default 0.0,         # 商品市场价(仅用作展示)
    profit              decimal(10,2) not null default 0.0,         # 商品利润
    sale_price          decimal(10,2) not null default 0.0,         # 商品销售价        
    state               tinyint not null default 0,                 # 商品状态
                                                                    # 0:无效 1:有效
                                                                    # 2:上架-展示在商城中
                                                                    # 3:下架-有效
                                                                    # 4:下架-无效

    image_url           varchar(255) not null default '',           # 展示图片

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`id`),
    index idx_goods_id(`goods_id`),
    index idx_category_id(category_id`)
)engine=InnoDB default charset=utf8;

-- 商品详情表
drop table if exists g_goods_detail
create table g_goods_detail (
    id                  int unsigned not null auto_increment,

    goods_id            int unsigned not null default 0,            # 商品ID
    description         text not null default '',                   # 商品详细描述
    image_urls          varchar(2048) not null default '',          # 商品轮播图片(json格式)
                                                                    # {"1":{"sort":1,"url":"xx"}}

    like_count          int unsigned not null default 0,            # 点赞计数

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`id`),
    index idx_goods_id(`goods_id`)
)engine=InnoDB default charset=utf8;

-- 商品分类表
drop table if exists g_category
create table g_category (
    id                  int unsigned not null auto_increment,

    category_id         int unsigned not null default 0,            # 品类ID
    name                varchar(255) not null default '',           # 品类名
    image_url           varchar(255) not null default '',           # 图标

    ctime               int not null default 0,                     # 创建时间
    mtime               int not null default 0,                     # 修改时间

    primary key (`id`),
    unique key key_category_id(`category_id`)
)engine=InnoDB default charset=utf8;

-- 商品点赞表
drop table if exists g_goods_like
create table g_goods_like (
    id                  int unsigned not null auto_increment,

    goods_id            int unsigned not null default 0,            # 商品ID
    user_id             int unsigned not null default 0,            # 评论用户ID

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_goods_id_user_id(`goods_id`, `user_id`)
)engine=InnoDB default charset=utf8;

-- 商品评价表
drop table if exists g_goods_comment
create table g_goods_comment (
    id                  int unsigned not null auto_increment,

    goods_id            int unsigned not null default 0,            # 商品ID
    order_id            char(16) not null default '',               # 所属订单ID

    user_id             int unsigned not null default 0,            # 评论用户ID
    nickname            varchar(255) not null default '',           # 评论用户名(冗余数据)
    score               int unsigned not null default 0,            # 商品评分
    content             varchar(1024) not null default '',          # 商品评价
    image_urls          varchar(2048) not null default '',          # 商品评价图片(json格式)
                                                                    # {"url":["http://xx",""]}

    kf_reply            varchar(1024) not null default '',          # 客服回复
    like_count          int unsigned not null default 0,            # 点赞计数
    state               tinyint not null default 0,                 # 评论状态 0:无效 1:有效

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_goods_id_state(`goods_id`, `state`),
    index idx_user_id_goods_id_order_id(`user_id`, `goods_id`, `order_id`)
)engine=InnoDB default charset=utf8;

-- 商品评价点赞表
drop table if exists g_goods_comment_like
create table g_goods_comment_like (
    id                  int unsigned not null auto_increment,

    comment_id          int unsigned not null default 0,            # 评价ID
    user_id             int unsigned not null default 0,            # 评论用户ID

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_comment_id_user_id(`comment_id`, `user_id`)
)engine=InnoDB default charset=utf8;

-----------------------------------活动相关表-----------------------------------
-- [商品分组]信息表
drop table if exists a_goods_group_info
create table g_goods_group_info (
    id                  int unsigned not null auto_increment,

    title               varchar(255) not null default '',           # 标题
    description         text not null default '',                   # 详细描述
    image_url           varchar(255) not null default '',           # 展示图片

    begin_time          int not null default 0,                     # 开始时间
    end_time            int not null default 0,                     # 结束时间

    state               tinyint not null default 0,                 # 状态 0:无效 1:有效
    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_comment_id_user_id(`comment_id`, `user_id`)
)engine=InnoDB default charset=utf8;

-- [商品分组]列表
drop table if exists a_goods_group_list
create table g_goods_group_list (
    id                  int unsigned not null auto_increment,

    group_id            int unsigned not null default 0,            # 分组ID

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`)
)engine=InnoDB default charset=utf8;

-- [商品分组中的商品]列表
drop table if exists a_goods_group_glist
create table g_goods_group_glist (
    id                  int unsigned not null auto_increment,

    group_id            int unsigned not null default 0,            # 分组ID
    sku_info            varchar(255) not null default '',           # sku 信息json格式{'id':[1,2], 'val':[33,34]}
    price               decimal(10,2) not null default 0.0,         # 商品价格

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
    index idx_comment_id_user_id(`comment_id`, `user_id`)
)engine=InnoDB default charset=utf8;

-----------------------------------系统内部表-----------------------------------
-- 系统地址对照表
drop table if exists s_address_table;
create table s_address_table (
    id                  int unsigned not null auto_increment,

    province_id         int not null default 0,                     # 省
    province_name       varchar(60) not null default '',            # 省名
    city_id             int not null default 0,                     # 市
    city_name           varchar(60) not null default '',            # 市名
    district_id         int not null default 0,                     # 区
    district_name       varchar(60) not null default '',            # 区名

    ctime               int not null default 0,                     # 创建时间

    primary key (`id`),
)engine=InnoDB default charset=utf8;

