-- # !sqlite
-- # { backpacks
-- #    { init
CREATE TABLE IF NOT EXISTS backpacks(
    username TEXT NOT NULL,
    type TEXT NOT NULL
)
-- #    }
-- #    { get
-- #        :username string
SELECT type FROM backpacks WHERE username = :username
-- #    }
-- #    { insert
-- #        :username string
-- #        :type string
INSERT OR REPLACE INTO backpacks(username, type) VALUES (:username, :type)
-- #    }
-- #    { upgrade
-- #        :username string
-- #        :type string
UPDATE backpacks SET type = :type WHERE username = :username
-- #    }
-- # }
