CREATE TABLE trips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    city VARCHAR(150) NOT NULL,

    ticket_price INT UNSIGNED NOT NULL,
    hotel_price INT UNSIGNED NOT NULL,
    nights SMALLINT UNSIGNED NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_trips_status
        CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'))
);
