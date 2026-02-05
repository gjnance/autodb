-- Postgres Explorer Schema
-- Metadata tables in 'autodb' schema, sample data in 'sample_data' schema

-- Create schemas
CREATE SCHEMA IF NOT EXISTS autodb;
CREATE SCHEMA IF NOT EXISTS sample_data;

-- ============================================
-- METADATA TABLES (autodb schema)
-- ============================================

-- rules table - Relational display rules
CREATE TABLE IF NOT EXISTS autodb.rules (
    id SERIAL PRIMARY KEY,
    schema_name VARCHAR(128) NOT NULL,
    table_name VARCHAR(128) NOT NULL,
    column_name VARCHAR(128) NOT NULL,
    map_schema VARCHAR(128) NOT NULL,
    map_table VARCHAR(128) NOT NULL,
    map_column VARCHAR(128) NOT NULL,
    map_display VARCHAR(128) NOT NULL,
    UNIQUE (schema_name, table_name, column_name)
);

COMMENT ON TABLE autodb.rules IS 'Relational rules for display columns';
COMMENT ON COLUMN autodb.rules.schema_name IS 'Source schema';
COMMENT ON COLUMN autodb.rules.table_name IS 'Source table';
COMMENT ON COLUMN autodb.rules.column_name IS 'Source column with numeric ID';
COMMENT ON COLUMN autodb.rules.map_schema IS 'Target schema for lookup';
COMMENT ON COLUMN autodb.rules.map_table IS 'Target table for lookup';
COMMENT ON COLUMN autodb.rules.map_column IS 'Target column to match (usually id)';
COMMENT ON COLUMN autodb.rules.map_display IS 'Target column to display';

-- user_preferences table - User preferences per table
CREATE TABLE IF NOT EXISTS autodb.user_preferences (
    id SERIAL PRIMARY KEY,
    schema_name VARCHAR(128),
    table_name VARCHAR(128),
    var VARCHAR(64),
    value VARCHAR(64),
    username VARCHAR(64) DEFAULT ''
);

COMMENT ON TABLE autodb.user_preferences IS 'User preferences for settings per table';
CREATE INDEX IF NOT EXISTS idx_prefs_user_table ON autodb.user_preferences(schema_name, table_name, var, username);

-- user_roles table - RBAC role assignments
CREATE TABLE IF NOT EXISTS autodb.user_roles (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(32) NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_role CHECK (role IN ('admin', 'editor', 'viewer'))
);

COMMENT ON TABLE autodb.user_roles IS 'Role-based access control assignments';
COMMENT ON COLUMN autodb.user_roles.email IS 'User email (from Azure AD)';
COMMENT ON COLUMN autodb.user_roles.role IS 'Role: admin (full access), user (read/write), viewer (read-only)';
CREATE INDEX IF NOT EXISTS idx_roles_email ON autodb.user_roles(email);

-- ============================================
-- SAMPLE DATA TABLES (sample_data schema)
-- ============================================

-- Countries table
CREATE TABLE IF NOT EXISTS sample_data.countries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    code VARCHAR(16)
);

INSERT INTO sample_data.countries (id, name, code) VALUES
    (1, 'Comic Stripia', 'CS')
ON CONFLICT (id) DO NOTHING;

SELECT setval('sample_data.countries_id_seq', (SELECT MAX(id) FROM sample_data.countries));

-- Regions table
CREATE TABLE IF NOT EXISTS sample_data.regions (
    id SERIAL PRIMARY KEY,
    region VARCHAR(255),
    country_id INTEGER
);

INSERT INTO sample_data.regions (id, region, country_id) VALUES
    (1, 'Imagination Land', 1)
ON CONFLICT (id) DO NOTHING;

SELECT setval('sample_data.regions_id_seq', (SELECT MAX(id) FROM sample_data.regions));

-- Localities table
CREATE TABLE IF NOT EXISTS sample_data.localities (
    id SERIAL PRIMARY KEY,
    locality VARCHAR(255),
    region_id INTEGER
);

INSERT INTO sample_data.localities (id, locality, region_id) VALUES
    (1, 'Comic Town', 1)
ON CONFLICT (id) DO NOTHING;

SELECT setval('sample_data.localities_id_seq', (SELECT MAX(id) FROM sample_data.localities));

-- Contacts table
CREATE TABLE IF NOT EXISTS sample_data.contacts (
    id SERIAL PRIMARY KEY,
    name_last VARCHAR(128) NOT NULL DEFAULT '',
    name_first VARCHAR(128) NOT NULL DEFAULT '',
    phone_mobile VARCHAR(32) DEFAULT '',
    phone_home VARCHAR(32) DEFAULT '',
    phone_work VARCHAR(32) DEFAULT '',
    phone_fax VARCHAR(32) DEFAULT NULL,
    street TEXT,
    locality_id INTEGER,
    region_id INTEGER,
    postcode VARCHAR(16) DEFAULT NULL,
    country_id INTEGER,
    email1 VARCHAR(128) DEFAULT '',
    email2 VARCHAR(128) DEFAULT '',
    birthdate DATE DEFAULT NULL,
    notes TEXT
);

-- Insert comic strip character data
INSERT INTO sample_data.contacts (id, name_last, name_first, phone_mobile, phone_home, phone_work, phone_fax, street, locality_id, region_id, postcode, country_id, email1, email2, birthdate, notes) VALUES
    (231, 'Brown', 'Charlie', '612-345-6789', '612-345-6790', '612-345-6791', '612-345-6792', '77 Birch Street', 1, 1, '55101', 1, 'charlie@peanuts.com', 'cbrown@peanuts.com', '1950-10-02', 'Has a dog named Snoopy.'),
    (232, 'Jones', 'Calvin', '513-555-1234', '513-555-1235', '513-555-1236', '513-555-1237', '450 Spaceman Spiff Drive', 1, 1, '45220', 1, 'calvin@calvinandhobbes.com', 'cjones@calvinandhobbes.com', '1985-11-18', 'Has an imaginary tiger friend named Hobbes.'),
    (233, 'Andrews', 'Archie', '413-555-7890', '413-555-7891', '413-555-7892', '413-555-7893', '123 Riverdale Lane', 1, 1, '01002', 1, 'archie@archiecomics.com', 'aandrews@archiecomics.com', '1941-12-22', 'Has a love triangle with Betty and Veronica.'),
    (234, 'McClure', 'Dilbert', '408-555-4567', '408-555-4568', '408-555-4569', '408-555-4570', '987 Cubicle Court', 1, 1, '95002', 1, 'dilbert@dilbert.com', 'dmcclure@dilbert.com', '1989-04-16', 'Lives with Dogbert and works in a cubicle.'),
    (235, 'Flagston', 'Hi', '415-555-8910', '415-555-8911', '415-555-8912', '415-555-8913', '123 Main Street', 1, 1, '94101', 1, 'hi@hiandlois.com', 'hflagston@hiandlois.com', '1954-10-18', 'Lives with wife Lois and four children.'),
    (236, 'Lockhorn', 'Leroy', '718-555-2345', '718-555-2346', '718-555-2347', '718-555-2348', '12 Suburbia Avenue', 1, 1, '10001', 1, 'leroy@lockhorns.com', 'llockhorn@lockhorns.com', '1968-09-09', 'Often fights with his wife Loretta.'),
    (237, 'Bumstead', 'Dagwood', '914-555-6789', '914-555-6790', '914-555-6791', '914-555-6792', '333 Blondie Street', 1, 1, '10601', 1, 'dagwood@blondie.com', 'dbumstead@blondie.com', '1930-09-08', 'Known for his tall sandwiches and napping on the couch.'),
    (238, 'Wilson', 'Dennis', '213-555-1234', '213-555-1235', '213-555-1236', '213-555-1237', '56 Menace Way', 1, 1, '90012', 1, 'dennis@dennisthemenace.com', 'dwilson@dennisthemenace.com', '1951-03-12', 'Known as the menace of the neighborhood.'),
    (239, 'Doonesbury', 'Mike', '802-555-7890', '802-555-7891', '802-555-7892', '802-555-7893', '777 Walden Street', 1, 1, '05601', 1, 'mike@doonesbury.com', 'mdoonesbury@doonesbury.com', '1970-10-26', 'A politically-minded individual with a witty sense of humor.'),
    (240, 'Oyl', 'Olive', '415-555-4567', '415-555-4568', '415-555-4569', '415-555-4570', '999 Popeye Place', 1, 1, '94101', 1, 'olive@popeye.com', 'ooyl@popeye.com', '1919-01-17', 'The longtime girlfriend of Popeye.'),
    (241, 'Patterson', 'Elly', '415-555-8911', '415-555-8912', '415-555-8913', '415-555-8914', '33 For Better Or For Worse Lane', 1, 1, '94101', 1, 'elly@fbowf.com', 'epatterson@fbowf.com', '1979-09-09', 'A loving mother and wife in the Patterson family.'),
    (242, 'Valiant', 'Prince', '415-555-1235', '415-555-1236', '415-555-1237', '415-555-1238', '888 King Features Blvd', 1, 1, '94101', 1, 'prince@valiant.com', 'pvaliant@valiant.com', '1937-02-13', 'A bold knight in the days of King Arthur.'),
    (243, 'Fooker', 'Jason', '408-555-2346', '408-555-2347', '408-555-2348', '408-555-2349', '456 GPF Street', 1, 1, '95002', 1, 'jason@gpf-comics.com', 'jfooker@gpf-comics.com', '1998-11-02', 'A key character in the tech-focused comic strip GPF.'),
    (244, 'Buckles', 'Get', '703-555-7892', '703-555-7893', '703-555-7894', '703-555-7895', '222 Doggie Drive', 1, 1, '20001', 1, 'get@getfuzzy.com', 'gbuckles@getfuzzy.com', '1999-09-06', 'The anthropomorphic pet dog of the comic strip Get Fuzzy.'),
    (245, 'Chance', 'Tank', '701-555-1238', '701-555-1239', '701-555-1240', '701-555-1241', '999 Gridiron Street', 1, 1, '58102', 1, 'tank@tankmcnamara.com', 'tchance@tankmcnamara.com', '1974-07-01', 'A former professional football player turned sportscaster.'),
    (246, 'Thornapple', 'Brutus', '216-555-4568', '216-555-4569', '216-555-4570', '216-555-4571', '1111 Born Loser Road', 1, 1, '44101', 1, 'brutus@bornloser.com', 'bthornapple@bornloser.com', '1965-05-10', 'The hapless and luckless protagonist of The Born Loser.'),
    (247, 'Duplex', 'Eno', '805-555-8914', '805-555-8915', '805-555-8916', '805-555-8917', '666 Dog Street', 1, 1, '93101', 1, 'eno@duplex.com', 'eduplex@duplex.com', '1980-02-04', 'The misanthropic dog owner of The Duplex.'),
    (248, 'Madison', 'Amber', '212-555-2340', '212-555-2341', '212-555-2342', '212-555-2343', '444 Luann Avenue', 1, 1, '10001', 1, 'amber@luann.com', 'amadison@luann.com', '1985-03-17', 'The popular girl in Luann, an American syndicated comic strip.'),
    (249, 'Funky', 'Winkerbean', '216-555-6782', '216-555-6783', '216-555-6784', '216-555-6785', '111 Westview Street', 1, 1, '44101', 1, 'funky@funky.com', 'fwinkerbean@funky.com', '1972-03-27', 'The title character of the long-running comic strip Funky Winkerbean.'),
    (250, 'Binkley', 'Michael', '408-555-7896', '408-555-7897', '408-555-7898', '408-555-7899', '333 Bloom County Blvd', 1, 1, '95002', 1, 'michael@bloomcounty.com', 'mbinkley@bloomcounty.com', '1980-12-08', 'A major character from the comic strip Bloom County.'),
    (251, 'Jughead', 'Jones', '413-555-5678', '413-555-5679', '413-555-5680', '413-555-5681', '999 Burger Street', 1, 1, '01002', 1, 'jughead@archiecomics.com', 'jjones@archiecomics.com', '1941-12-22', 'Loves to eat and is best friends with Archie Andrews.'),
    (252, 'Zonker', 'Harris', '802-555-2345', '802-555-2346', '802-555-2347', '802-555-2348', '222 Walden Street', 1, 1, '05601', 1, 'zonker@doonesbury.com', 'zharris@doonesbury.com', '1970-10-26', 'Known for his laid-back perspective in the comic strip Doonesbury.'),
    (253, 'Ketcham', 'Dennis', '213-555-4567', '213-555-4568', '213-555-4569', '213-555-4570', '56 Menace Way', 1, 1, '90012', 1, 'dennis@dennisthemenace.com', 'dketcham@dennisthemenace.com', '1951-03-12', 'A young boy who always means well, but often gets into trouble.'),
    (254, 'Griffith', 'Zippy', '415-555-7891', '415-555-7892', '415-555-7893', '415-555-7894', '123 Dingburg Street', 1, 1, '94101', 1, 'zippy@zippythepinhead.com', 'zgriffith@zippythepinhead.com', '1971-03-07', 'A microcephalic with a joyous, enthusiastic, and unfocused personality.'),
    (255, 'Dinkle', 'Harold', '513-555-1237', '513-555-1238', '513-555-1239', '513-555-1240', '456 Scapegoat Street', 1, 1, '45220', 1, 'harold@funkywinkerbean.com', 'hdinkle@funkywinkerbean.com', '1972-03-27', 'Former band director at Westview High School.'),
    (256, 'Bumstead', 'Blondie', '914-555-6783', '914-555-6784', '914-555-6785', '914-555-6786', '333 Blondie Street', 1, 1, '10601', 1, 'blondie@blondie.com', 'bbumstead@blondie.com', '1930-09-08', 'Dagwood Bumstead''s patient and understanding wife.'),
    (257, 'Trudeau', 'Doonesbury', '802-555-3456', '802-555-3457', '802-555-3458', '802-555-3459', '777 Walden Street', 1, 1, '05601', 1, 'doonesbury@doonesbury.com', 'dtrudeau@doonesbury.com', '1970-10-26', 'A character from the comic strip Doonesbury, known for its political and social commentary.'),
    (258, 'Smith', 'Cathy', '408-555-6789', '408-555-6790', '408-555-6791', '408-555-6792', '321 Women''s Issues Lane', 1, 1, '95002', 1, 'cathy@cathy.com', 'csmith@cathy.com', '1976-11-22', 'Known for her struggles with the four basic guilt groups: food, love, mom, and work.'),
    (259, 'Wilson', 'Gahan', '312-555-2341', '312-555-2342', '312-555-2343', '312-555-2344', '123 Spooky Street', 1, 1, '60601', 1, 'gahan@spooky.com', 'gwilson@spooky.com', '1964-01-01', 'Known for his uniquely eerie cartoons.'),
    (260, 'Mauldin', 'Willie', '212-555-4560', '212-555-4561', '212-555-4562', '212-555-4563', '777 Up Front Street', 1, 1, '10001', 1, 'willie@upfront.com', 'mmauldin@upfront.com', '1945-01-01', 'One of two World War II infantrymen in the comic strip Up Front.')
ON CONFLICT (id) DO NOTHING;

SELECT setval('sample_data.contacts_id_seq', (SELECT MAX(id) FROM sample_data.contacts));

-- ============================================
-- SAMPLE RELATION RULES
-- ============================================

INSERT INTO autodb.rules (schema_name, table_name, column_name, map_schema, map_table, map_column, map_display) VALUES
    ('sample_data', 'contacts', 'country_id', 'sample_data', 'countries', 'id', 'name'),
    ('sample_data', 'contacts', 'region_id', 'sample_data', 'regions', 'id', 'region'),
    ('sample_data', 'contacts', 'locality_id', 'sample_data', 'localities', 'id', 'locality'),
    ('sample_data', 'regions', 'country_id', 'sample_data', 'countries', 'id', 'name'),
    ('sample_data', 'localities', 'region_id', 'sample_data', 'regions', 'id', 'region')
ON CONFLICT (schema_name, table_name, column_name) DO NOTHING;
