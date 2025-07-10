CREATE TABLE map_about (
   id INT auto_increment NOT NULL,
   alias_map varchar(80) DEFAULT '' NULL COMMENT 'id карты',
   edit_date DATETIME DEFAULT current_timestamp() NULL,
   edit_whois INT DEFAULT 0 NULL COMMENT 'id редактора',
   edit_ipv4 BIGINT DEFAULT 0 NULL,
   content LONGTEXT NULL,
   edit_comment varchar(120) DEFAULT '' NULL,
   is_publicity enum('ANYONE','VISITOR','EDITOR','OWNER','ROOT') DEFAULT 'ANYONE' NULL,
   CONSTRAINT map_about_pk PRIMARY KEY (id)
)
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_general_ci;
CREATE INDEX map_about_alias_map_IDX USING BTREE ON map_about (alias_map);

ALTER TABLE map_about ADD title varchar(250) DEFAULT '' NULL COMMENT 'название карты';

