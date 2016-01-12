
-- shaowei 2015-12-27
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

