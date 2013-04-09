CREATE TABLE user_account (
  id BIGSERIAL NOT NULL,
  display_name VARCHAR(255) DEFAULT NULL,
  password_crypted VARCHAR(255) DEFAULT NULL,
  password_salt VARCHAR(40) DEFAULT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  forgotten_password_code VARCHAR(20) NULL,
  forgotten_password_code_generated_at TIMESTAMP WITHOUT TIME ZONE NULL,
  year_of_birth SMALLINT NULL,
  gender VARCHAR(1) NULL,
  avatar_key VARCHAR(100) DEFAULT NULL,
  system_admin BOOLEAN NOT NULL DEFAULT 'f',
  use_advanced_schedule BOOLEAN NOT NULL DEFAULT 'f',
  PRIMARY KEY(id)
);

CREATE TABLE user_email (
  id BIGSERIAL NOT NULL,
  user_account_id BIGINT NOT NULL,
  title VARCHAR(255) DEFAULT 'main' NOT NULL, /** NOW UNUSED **/
  email VARCHAR(255) NOT NULL,
  confirm_code VARCHAR(50) DEFAULT NULL,
  send_before_confirmation BOOLEAN NOT NULL DEFAULT 't',
  stop_send_before_confirmation_code VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  deleted_at TIMESTAMP WITHOUT TIME ZONE NULL,
  simple_schedule_on_days VARCHAR(255) DEFAULT 'mon,tue,wed,thu,fri,sat,sun' NOT NULL,
  simple_schedule_from_time TIME WITHOUT TIME ZONE DEFAULT '00:00',
  simple_schedule_to_time TIME WITHOUT TIME ZONE  DEFAULT '23:59',
  PRIMARY KEY(id)
);
CREATE UNIQUE INDEX user_email_email ON user_email (email, deleted_at);
ALTER TABLE user_email ADD CONSTRAINT user_email_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE user_twitter (
  id BIGSERIAL NOT NULL,
  user_account_id BIGINT NOT NULL,
  title VARCHAR(255) DEFAULT 'main' NOT NULL, /** NOW UNUSED **/
  username VARCHAR(100) NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  deleted_at TIMESTAMP WITHOUT TIME ZONE NULL,
  simple_schedule_on_days VARCHAR(255) DEFAULT 'mon,tue,wed,thu,fri,sat,sun' NOT NULL,
  simple_schedule_from_time TIME WITHOUT TIME ZONE DEFAULT '00:00',
  simple_schedule_to_time TIME WITHOUT TIME ZONE  DEFAULT '23:59',
  PRIMARY KEY(id)
);
CREATE UNIQUE INDEX user_twitter_username ON user_twitter (username, deleted_at);
ALTER TABLE user_twitter ADD CONSTRAINT user_twitter_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE country (
  id SERIAL NOT NULL,
  iso_code_2char VARCHAR(2) NOT NULL,
  title VARCHAR(255) NOT NULL,
  international_dailing_code VARCHAR(20) NOT NULL,
  international_dailing_prefix VARCHAR(20) NOT NULL,
  PRIMARY KEY(id)
);
INSERT INTO country (iso_code_2char,title,international_dailing_code,international_dailing_prefix) VALUES ('gb','United Kingdom','44','00');

CREATE TABLE user_telephone (
  id BIGSERIAL NOT NULL,
  user_account_id BIGINT NOT NULL,
  title VARCHAR(255) DEFAULT 'main' NOT NULL, /** NOW UNUSED **/
  country_id INT NOT NULL,
  call_number VARCHAR(50) NOT NULL,
  confirm_code VARCHAR(10) DEFAULT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  deleted_at TIMESTAMP WITHOUT TIME ZONE NULL,
  simple_schedule_on_days VARCHAR(255) DEFAULT 'mon,tue,wed,thu,fri,sat,sun' NOT NULL,
  simple_schedule_from_time TIME WITHOUT TIME ZONE DEFAULT '00:00',
  simple_schedule_to_time TIME WITHOUT TIME ZONE  DEFAULT '23:59',
  PRIMARY KEY(id)
);
CREATE UNIQUE INDEX user_telephone_number ON user_telephone (country_id,call_number, deleted_at);
ALTER TABLE user_telephone ADD CONSTRAINT user_telephone_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);
ALTER TABLE user_telephone ADD CONSTRAINT user_telephone_country_id  FOREIGN KEY (country_id) REFERENCES country(id);

CREATE TABLE user_on_holiday (
  id BIGSERIAL NOT NULL,
  user_account_id BIGINT NOT NULL,
  holiday_from TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  holiday_to TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  share_with_group BOOLEAN NOT NULL DEFAULT 'f',
  description TEXT NULL,
  PRIMARY KEY(id)
);
ALTER TABLE user_on_holiday ADD CONSTRAINT user_on_holiday_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE user_session (
  user_account_id BIGINT NOT NULL,
  id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  last_used_at TIMESTAMP WITHOUT TIME ZONE NULL,
  PRIMARY KEY(user_account_id, id)
);
ALTER TABLE user_session ADD CONSTRAINT user_session_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE white_label (
  id BIGSERIAL NOT NULL,
  title VARCHAR(255) NOT NULL,
  PRIMARY KEY(id)
);


CREATE TABLE user_admins_white_label (
  user_account_id BIGINT NOT NULL,
  white_label_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (user_account_id,white_label_id)
);
ALTER TABLE user_admins_white_label ADD CONSTRAINT user_admins_white_label_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);
ALTER TABLE user_admins_white_label ADD CONSTRAINT user_admins_white_label_white_label_id  FOREIGN KEY (white_label_id) REFERENCES white_label(id);

CREATE TABLE support_group (
  id BIGSERIAL NOT NULL,
  white_label_id BIGINT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  sys_admin_label VARCHAR(255) NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  deleted_at TIMESTAMP WITHOUT TIME ZONE NULL,
  is_premium BOOLEAN NOT NULL DEFAULT false,
  avatar_key VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY(id)
);
ALTER TABLE support_group ADD CONSTRAINT support_group_white_label_id  FOREIGN KEY (white_label_id) REFERENCES white_label(id);

CREATE TABLE user_in_group (
  user_account_id BIGINT NOT NULL,
  support_group_id BIGINT NOT NULL,
  invited_by_user_account_id BIGINT NULL,
  is_admin BOOLEAN NOT NULL DEFAULT false,
  can_make_requests BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (user_account_id,support_group_id)
);
ALTER TABLE user_in_group ADD CONSTRAINT user_in_group_user_account_id  FOREIGN KEY (user_account_id) REFERENCES user_account(id);
ALTER TABLE user_in_group ADD CONSTRAINT user_in_group_invited_by_user_account_id  FOREIGN KEY (invited_by_user_account_id) REFERENCES user_account(id);
ALTER TABLE user_in_group ADD CONSTRAINT user_in_group_support_group_id  FOREIGN KEY (support_group_id) REFERENCES support_group(id);


CREATE TABLE request_type (
  id BIGSERIAL NOT NULL,
  title VARCHAR(255) NOT NULL,
  support_group_id BIGINT NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY(id)
);
CREATE UNIQUE INDEX request_type_title ON request_type (support_group_id,title);
ALTER TABLE request_type ADD CONSTRAINT request_type_support_group_id FOREIGN KEY (support_group_id) REFERENCES support_group(id);

CREATE TABLE request (
  id BIGSERIAL NOT NULL,
  summary VARCHAR(140) NOT NULL,
  request TEXT NULL,
  support_group_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  created_by_user_id BIGINT NOT NULL,
  closed_at TIMESTAMP WITHOUT TIME ZONE,
  closed_by_user_id BIGINT NULL,
  cancelled_at TIMESTAMP WITHOUT TIME ZONE,
  cancelled_by_user_id BIGINT NULL,
  start_at TIMESTAMP WITHOUT TIME ZONE NULL,
  end_at TIMESTAMP WITHOUT TIME ZONE NULL,
  to_all_members BOOLEAN NOT NULL DEFAULT false,
  PRIMARY KEY(id)
 );
ALTER TABLE request ADD CONSTRAINT request_support_group_id FOREIGN KEY (support_group_id) REFERENCES support_group(id);
ALTER TABLE request ADD CONSTRAINT request_created_by_user_id FOREIGN KEY (created_by_user_id) REFERENCES user_account(id);
ALTER TABLE request ADD CONSTRAINT request_closed_by_user_id FOREIGN KEY (closed_by_user_id) REFERENCES user_account(id);
ALTER TABLE request ADD CONSTRAINT request_cancelled_by_user_id FOREIGN KEY (cancelled_by_user_id) REFERENCES user_account(id);

CREATE TABLE request_has_type (
  request_id BIGINT NOT NULL,
  request_type_id BIGINT NOT NULL,
  PRIMARY KEY (request_id,request_type_id)
);
ALTER TABLE request_has_type ADD CONSTRAINT request_has_type_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_has_type ADD CONSTRAINT request_has_type_request_type_id FOREIGN KEY (request_type_id) REFERENCES request_type(id);

CREATE TABLE request_to_user (
  request_id BIGINT NOT NULL,
  user_account_id BIGINT NOT NULL,
  PRIMARY KEY (request_id,user_account_id)
);
ALTER TABLE request_to_user ADD CONSTRAINT request_to_user_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_to_user ADD CONSTRAINT request_to_user_user_account_id FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE request_sent_to_user_email (
  request_id BIGINT NOT NULL,
  user_email_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_id,user_email_id)
);
ALTER TABLE request_sent_to_user_email ADD CONSTRAINT request_sent_to_user_email_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_sent_to_user_email ADD CONSTRAINT request_sent_to_user_email_user_email_id FOREIGN KEY (user_email_id) REFERENCES user_email(id);

CREATE TABLE request_sent_to_user_twitter (
  request_id BIGINT NOT NULL,
  user_twitter_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_id,user_twitter_id)
);
ALTER TABLE request_sent_to_user_twitter ADD CONSTRAINT request_sent_to_user_twitter_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_sent_to_user_twitter ADD CONSTRAINT request_sent_to_user_twitter_user_twitter_id FOREIGN KEY (user_twitter_id) REFERENCES user_twitter(id);

CREATE TABLE request_sent_to_user_telephone (
  request_id BIGINT NOT NULL,
  user_telephone_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_id,user_telephone_id)
);
ALTER TABLE request_sent_to_user_telephone ADD CONSTRAINT request_sent_to_user_telephone_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_sent_to_user_telephone ADD CONSTRAINT request_sent_to_user_telephone_user_telephone_id FOREIGN KEY (user_telephone_id) REFERENCES user_telephone(id);

CREATE TABLE request_response (
  id BIGSERIAL NOT NULL,
  request_id BIGINT NOT NULL,
  user_account_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  response TEXT,
  PRIMARY KEY (id)
);
ALTER TABLE request_response ADD CONSTRAINT request_response_request_id FOREIGN KEY (request_id) REFERENCES request(id);
ALTER TABLE request_response ADD CONSTRAINT request_response_user_account_id FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE request_response_sent_to_user_email (
  request_response_id BIGINT NOT NULL,
  user_email_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_response_id,user_email_id)
);
ALTER TABLE request_response_sent_to_user_email ADD CONSTRAINT request_response_sent_to_user_email_request_response_id FOREIGN KEY (request_response_id) REFERENCES request_response(id);
ALTER TABLE request_response_sent_to_user_email ADD CONSTRAINT request_response_sent_to_user_email_user_email_id FOREIGN KEY (user_email_id) REFERENCES user_email(id);

CREATE TABLE request_response_sent_to_user_twitter (
  request_response_id BIGINT NOT NULL,
  user_twitter_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_response_id,user_twitter_id)
);
ALTER TABLE request_response_sent_to_user_twitter ADD CONSTRAINT request_response_sent_to_user_twitter_request_id FOREIGN KEY (request_response_id) REFERENCES request_response(id);
ALTER TABLE request_response_sent_to_user_twitter ADD CONSTRAINT request_response_sent_to_user_twitter_user_twitter_id FOREIGN KEY (user_twitter_id) REFERENCES user_twitter(id);

CREATE TABLE request_response_sent_to_user_telephone (
  request_response_id BIGINT NOT NULL,
  user_telephone_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (request_response_id,user_telephone_id)
);
ALTER TABLE request_response_sent_to_user_telephone ADD CONSTRAINT request_response_sent_to_user_telephone_request_id FOREIGN KEY (request_response_id) REFERENCES request_response(id);
ALTER TABLE request_response_sent_to_user_telephone ADD CONSTRAINT request_response_sent_to_user_telephone_user_telephone_id FOREIGN KEY (user_telephone_id) REFERENCES user_telephone(id);

CREATE TABLE simple_schedule_request_type_rule (
  user_account_id BIGINT NOT NULL,
  request_type_id BIGINT NOT NULL,
  send BOOLEAN NOT NULL DEFAULT 't',
  PRIMARY KEY(user_account_id,request_type_id)
);
ALTER TABLE simple_schedule_request_type_rule ADD CONSTRAINT simple_schedule_request_type_rule_user_account_id FOREIGN KEY (user_account_id) REFERENCES user_account(id);
ALTER TABLE simple_schedule_request_type_rule ADD CONSTRAINT simple_schedule_request_type_rule_request_type_id FOREIGN KEY (request_type_id) REFERENCES request_type(id);




CREATE TABLE schedule_rule (
  id BIGSERIAL NOT NULL,
  user_account_id BIGINT NOT NULL,
  sort_order SMALLINT,
  from_time TIME WITHOUT TIME ZONE,
  to_time TIME WITHOUT TIME ZONE,
  days VARCHAR(100),
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY(id)
);
ALTER TABLE schedule_rule ADD CONSTRAINT schedule_rule_user_account_id FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE schedule_rule_for_user_email (
  schedule_rule_id BIGINT NOT NULL,
  user_email_id BIGINT NOT NULL,
  PRIMARY KEY (schedule_rule_id,user_email_id)
);
ALTER TABLE schedule_rule_for_user_email ADD CONSTRAINT schedule_rule_for_user_email_schedule_rule_id FOREIGN KEY (schedule_rule_id) REFERENCES schedule_rule(id);
ALTER TABLE schedule_rule_for_user_email ADD CONSTRAINT schedule_rule_for_user_email_user_email_id FOREIGN KEY (user_email_id) REFERENCES user_email(id);

CREATE TABLE schedule_rule_for_user_telephone (
  schedule_rule_id BIGINT NOT NULL,
  user_telephone_id BIGINT NOT NULL,
  PRIMARY KEY (schedule_rule_id,user_telephone_id)
);
ALTER TABLE schedule_rule_for_user_telephone ADD CONSTRAINT schedule_rule_for_user_telephone_schedule_rule_id FOREIGN KEY (schedule_rule_id) REFERENCES schedule_rule(id);
ALTER TABLE schedule_rule_for_user_telephone ADD CONSTRAINT schedule_rule_for_user_telephone_user_telephone_id FOREIGN KEY (user_telephone_id) REFERENCES user_telephone(id);

CREATE TABLE schedule_rule_for_user_twitter (
  schedule_rule_id BIGINT NOT NULL,
  user_twitter_id BIGINT NOT NULL,
  PRIMARY KEY (schedule_rule_id,user_twitter_id)
);
ALTER TABLE schedule_rule_for_user_twitter ADD CONSTRAINT schedule_rule_for_user_twitter_schedule_rule_id FOREIGN KEY (schedule_rule_id) REFERENCES schedule_rule(id);
ALTER TABLE schedule_rule_for_user_twitter ADD CONSTRAINT schedule_rule_for_user_twitter_user_telephone_id FOREIGN KEY (user_twitter_id) REFERENCES user_twitter(id);

CREATE TABLE schedule_rule_for_request_type (
  schedule_rule_id BIGINT NOT NULL,
  request_type_id BIGINT NOT NULL,
  PRIMARY KEY (schedule_rule_id,request_type_id)
);
ALTER TABLE schedule_rule_for_request_type ADD CONSTRAINT schedule_rule_for_request_type_schedule_rule_id FOREIGN KEY (schedule_rule_id) REFERENCES schedule_rule(id);
ALTER TABLE schedule_rule_for_request_type ADD CONSTRAINT schedule_rule_for_request_type_request_type_id FOREIGN KEY (request_type_id) REFERENCES request_type(id);

CREATE TABLE sms_in (
  id BIGSERIAL NOT NULL,
  twilio_msg_id VARCHAR(34) NULL,
  from_number VARCHAR(100) NOT NULL,
  body VARCHAR(200) NOT NULL,
  user_telephone_id BIGINT NULL,
  request_response_id BIGINT NULL,
  request_id BIGINT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (id)
);
ALTER TABLE sms_in ADD CONSTRAINT sms_in_user_telephone_id FOREIGN KEY (user_telephone_id) REFERENCES user_telephone(id);
ALTER TABLE sms_in ADD CONSTRAINT sms_in_request_response_id FOREIGN KEY (request_response_id) REFERENCES request_response(id);
ALTER TABLE sms_in ADD CONSTRAINT sms_in_request_id FOREIGN KEY (request_id) REFERENCES request(id);


CREATE TABLE pilot_users (
  id BIGSERIAL NOT NULL,
  about TEXT,
  name TEXT,
  email TEXT,
  phone TEXT,
  PRIMARY KEY (id)
);


CREATE TABLE support_group_news_article (
  id BIGSERIAL NOT NULL,
  summary VARCHAR(500) NOT NULL,
  body TEXT NULL,
  support_group_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  created_by_user_id BIGINT NOT NULL,
  PRIMARY KEY(id)
 );
ALTER TABLE support_group_news_article ADD CONSTRAINT support_group_news_article_support_group_id FOREIGN KEY (support_group_id) REFERENCES support_group(id);
ALTER TABLE support_group_news_article ADD CONSTRAINT support_group_news_article_created_by_user_id FOREIGN KEY (created_by_user_id) REFERENCES user_account(id);

CREATE TABLE support_group_news_article_sent_to_user_email (
  support_group_news_article_id BIGINT NOT NULL,
  user_email_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (support_group_news_article_id,user_email_id)
);
ALTER TABLE support_group_news_article_sent_to_user_email ADD CONSTRAINT support_group_news_article_sent_to_user_email_group_news_id FOREIGN KEY (support_group_news_article_id) REFERENCES support_group_news_article(id);
ALTER TABLE support_group_news_article_sent_to_user_email ADD CONSTRAINT support_group_news_article_sent_to_user_email_user_email_id FOREIGN KEY (user_email_id) REFERENCES user_email(id);

CREATE TABLE support_group_news_article_response (
  id BIGSERIAL NOT NULL,
  support_group_news_article_id BIGINT NOT NULL,
  user_account_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  response TEXT,
  PRIMARY KEY (id)
);
ALTER TABLE support_group_news_article_response ADD CONSTRAINT support_group_news_article_response_group_news_id FOREIGN KEY (support_group_news_article_id) REFERENCES support_group_news_article(id);
ALTER TABLE support_group_news_article_response ADD CONSTRAINT support_group_news_article_response_user_account_id FOREIGN KEY (user_account_id) REFERENCES user_account(id);

CREATE TABLE support_group_news_article_response_sent_to_user_email (
  support_group_news_article_response_id BIGINT NOT NULL,
  user_email_id BIGINT NOT NULL,
  sent_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  PRIMARY KEY (support_group_news_article_response_id,user_email_id)
);
ALTER TABLE support_group_news_article_response_sent_to_user_email ADD CONSTRAINT support_group_news_article_response_sent_to_user_email_response_id FOREIGN KEY (support_group_news_article_response_id) REFERENCES support_group_news_article_response(id);
ALTER TABLE support_group_news_article_response_sent_to_user_email ADD CONSTRAINT support_group_news_article_response_sent_to_user_email_user_email_id FOREIGN KEY (user_email_id) REFERENCES user_email(id);

