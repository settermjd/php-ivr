DROP TABLE IF EXISTS ivr_input;

CREATE TABLE IF NOT EXISTS ivr_input (
    caller_phone_number TEXT PRIMARY KEY,
    language TEXT NULL CHECK( language IN ( "english", "spanish" ) ),
    department TEXT NULL CHECK( department IN ( "insurance", "banking" ) ),
    insurance_category TEXT NULL CHECK( insurance_category IN ( "personal", "commercial" ) ),
    insurance_type TEXT NULL CHECK( insurance_type IN ("home-and-contents", "car" ) ),
    new_or_existing_policy TEXT NULL CHECK( new_or_existing_policy IN ("new", "existing" ) ),
    text_copy_of_conversation INTEGER NULL DEFAULT 0,
    personal_details TEXT NULL,
    policy_number TEXT NULL,
    created_on TEXT DEFAULT CURRENT_TIMESTAMP
);

PRAGMA integrity_check;
