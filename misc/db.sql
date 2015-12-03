-- tinyint     0~127
-- smallint    0~32767
-- int         0~2187483647
-- bigint
-- engine=InnoDB  or  engine=MyISAM

-----------------------------------用户表-----------------------------------
drop table if exists u_user;
create table u_user (
    id                  int unsigned not null auto_increment comment 'id',

    phone               char(11) not null default '' comment '手机号',

    nickname            varchar(255) not null default '' comment '用户昵称',
    sex                 tinyint not null default 0 comment '用户性别 1男,2女,0未知',
    headimgurl          varchar(255) not null default '' comment '用户头像',

    state               tinyint not null default 0 comment '用户状态',

    ctime               int not null default 0 comment '创建时间',

    primary key (`id`),
    index idx_phone(`phone`),
    index idx_nickname(`nickname`)
)engine=InnoDB default charset=utf8 comment='用户基本信息表';

drop table if exists u_wx_user;
create table u_wx_user (
    id                  int unsigned not null auto_increment comment 'id',

    user_id             int unsigned not null default 0 comment 'user id',
    openid              varchar(63) not null default '' comment 'openid',
    nickname            varchar(255) not null default '' comment '用户昵称',
    sex                 tinyint not null default 0 comment '用户性别 1男,2女,0未知',
    headimgurl          varchar(255) not null default '' comment '用户头像',
    province            varchar(60) not null default '' comment '省',
    city                varchar(60) not null default '' comment '市',
    subscribe           tinyint not null default 0 comment '是否关注 0/1',
    subscribe_time      int not null default 0 comment '关注时间(取最后一次关注)',
    subscribe_from      tinyint not null default 0 comment '关注方式(仅记首次) 1:已经关注 2:普通关注 3:场景二维码',
    unionid             varchar(63) not null default '' comment '腾讯平台唯一ID',
    lng                 decimal(12,8) not null default '0.0' comment '经度180.12345678',
    lat                 decimal(12,8) not null default '0.0' comment '纬度180.12345678',

    state               tinyint not null default 0 comment '用户状态',

    ctime               int not null default 0 comment '创建时间',
    atime               int not null default 0 comment '与公众号交互时间',

    primary key (`id`),
    index idx_user_id(`user_id`),
    unique key key_openid(`openid`)
)engine=InnoDB default charset=utf8 comment='微信用户信息表';

drop table if exists u_user_address;
create table u_user_address (
    id                  int unsigned not null auto_increment comment 'id',

    user_id             int unsigned not null default 0 comment 'user id',

    re_name             varchar(31) not null default '' comment '收件人姓名',
    re_phone            char(11) not null default '' comment '收件人手机号',
    addr_type           tinyint not null default 0 comment '地址类型 0:未知 1:公司 2:家庭',

    province_id         int not null default 0 comment '省',
    city_id             int not null default 0 comment '市',
    district_id         int not null default 0 comment '区',
    detail              varchar(255) not null default '' comment '详细街道地址',
    re_id_card          varchar(18) not null default '' comment '收件人身份证',

    is_default          tinyint not null default 0 comment '是否为默认地址 0/1',

    ctime               int not null default 0 comment '创建时间',

    primary key (`id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8 comment='用户地址表';

-----------------------------------系统内部表-----------------------------------
drop table if exists s_address_table;
create table s_address_table (
    id                  int unsigned not null auto_increment comment 'id',

    province_id         int not null default 0 comment '省',
    province_name       varchar(31) not null default '' comment '省名',
    city_id             int not null default 0 comment '市',
    city_name           varchar(31) not null default '' comment '市名',
    district_id         int not null default 0 comment '区',
    district_name       varchar(31) not null default '' comment '区名',

    ctime               int not null default 0 comment '创建时间',

    primary key (`id`),
    index idx_user_id(`user_id`)
)engine=InnoDB default charset=utf8 comment='系统地址对照表';

