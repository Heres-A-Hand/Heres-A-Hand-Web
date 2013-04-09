ALTER TABLE saved_request ADD deleted_at TIMESTAMP WITHOUT TIME ZONE NULL;
ALTER TABLE saved_request ADD deleted_by_user_id BIGINT NULL;
ALTER TABLE saved_request ADD CONSTRAINT saved_request_deleted_by_user_id FOREIGN KEY (deleted_by_user_id) REFERENCES user_account(id);