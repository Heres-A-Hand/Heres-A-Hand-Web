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