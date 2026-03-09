CREATE TABLE IF NOT EXISTS ivr_input (
    caller_phone_number TEXT PRIMARY KEY,
    language TEXT NOT NULL DEFAULT "english" CHECK( language = "english" OR language = "spanish"),
    department TEXT NOT NULL DEFAULT "insurance" CHECK( department = "insurance" OR department = "banking"),
    insurance_category TEXT NOT NULL DEFAULT "personal" CHECK( department = "personal" OR department = "commercial"),
    insurance_type TEXT NOT NULL DEFAULT "home & contents" CHECK( department = "home & contents" OR department = "car"),
    new_or_existing_policy TEXT NOT NULL DEFAULT "new" CHECK( department = "new" OR department = "existing"),
    text_copy_of_conversation INTEGER NOT NULL DEFAULT 0,
    personal_details TEXT NOT NULL,
    policy_number TEXT NOT NULL,
    created_on TEXT DEFAULT CURRENT_TIMESTAMP
);
PRAGMA integrity_check;
