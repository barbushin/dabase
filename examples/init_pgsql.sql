DROP TABLE IF EXISTS "dabase_photos";

CREATE TABLE dabase_photos (
  "id"        serial NOT NULL PRIMARY KEY,
  "userId"  integer NOT NULL,
  "file"      varchar(100) NOT NULL,
  "name"    varchar(100) NOT NULL
) WITH (
    OIDS = FALSE
);
INSERT INTO "dabase_photos"("id","userId","file","name") VALUES (1,1,'0544f790141c422d970663e85be39f54','me and mike'),(2,2,'147487d65c842446f0ea327934846e1a',''),(3,2,'30e6bb0597d734d7ace70df4434ed17f','in London'),(4,3,'a0a414c2767ec6ac0c35aa3a22a88043','HNY 2008'),(5,3,'d697ae471f45331a2cb527e805002e69',''),(6,3,'64eef35fc11d4bd4d5a079bcfee572d7','friends 4ever');

DROP TABLE IF EXISTS "dabase_photos_comments";
CREATE TABLE "dabase_photos_comments" (
  "id"        serial NOT NULL PRIMARY KEY,
  "userId"  integer NOT NULL,
  "photoId"  integer NOT NULL,
  "text" text
) WITH (
    OIDS = FALSE
);
INSERT INTO "dabase_photos_comments"("id","userId","photoId","text") VALUES (1,1,4,'very nice'),(2,2,4,'i know this guy?'),(3,1,3,'hhhahhaha))) so funny!'),(4,2,3,':)'),(5,2,1,'LOL'),(6,3,1,'WTF does LOL ever means???');


DROP TABLE IF EXISTS "dabase_users";
CREATE TABLE "dabase_users" (
  "id"        serial NOT NULL PRIMARY KEY,
  "login" varchar(255) NOT NULL,
  "password" varchar(255) NOT NULL,
  "posts" integer NOT NULL default '0',
  "isModerator" integer NOT NULL default '0',
  "isRoot" integer NOT NULL default '0',
  "isActive" integer NOT NULL default '0'
) WITH (
    OIDS = FALSE
);
INSERT INTO "dabase_users"("login","password","posts","isModerator","isRoot","isActive") VALUES ('andrey','8aaffd2c9c0341ec6fb91a8bc7d194f8',135,1,0,1),('olya','7dc2e994d82a3b5b2a6d44743763a706',14,1,1,0),('sergey','7dc2e994d82a3b5b2a6d44743763a322',52,0,0,1);

DROP TABLE IF EXISTS "dabase_videos";
CREATE TABLE "dabase_videos" (
  "id"        serial NOT NULL PRIMARY KEY,
  "userId"  integer NOT NULL,
  "file" varchar(255) NOT NULL,
  "name" varchar(255) NOT NULL
) WITH (
    OIDS = FALSE
);
INSERT INTO "dabase_videos"("id","userId","file","name") VALUES (1,1,'0544f790141c422d970663e85be39f54','costa rico'),(2,2,'147487d65c842446f0ea327934846e1a','my brother'),(3,2,'30e6bb0597d734d7ace70df4434ed17f',''),(4,3,'a0a414c2767ec6ac0c35aa3a22a88043',''),(5,3,'d697ae471f45331a2cb527e805002e69','Jake'),(6,3,'64eef35fc11d4bd4d5a079bcfee572d7','');

DROP TABLE IF EXISTS "dabase_directories_tree";
CREATE TABLE "dabase_directories_tree" (
  "id"        serial NOT NULL PRIMARY KEY,
  "leftId" integer NOT NULL,
  "rightId" integer NOT NULL,
  "parentId" integer NOT NULL DEFAULT 0,
  "level" integer NOT NULL,
  "name" varchar(255) DEFAULT NULL
) WITH (
    OIDS = FALSE
);
