CREATE TABLE saved_request (
  id BIGSERIAL NOT NULL,
  summary VARCHAR(140) NOT NULL,
  request TEXT NULL,
  support_group_id BIGINT NOT NULL,
  created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  created_by_user_id BIGINT NOT NULL,
  updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
  updated_by_user_id BIGINT NOT NULL,
  PRIMARY KEY(id)
 );
ALTER TABLE saved_request ADD CONSTRAINT saved_request_created_by_user_id FOREIGN KEY (created_by_user_id) REFERENCES user_account(id);
ALTER TABLE saved_request ADD CONSTRAINT saved_request_updated_by_user_id FOREIGN KEY (updated_by_user_id) REFERENCES user_account(id);
