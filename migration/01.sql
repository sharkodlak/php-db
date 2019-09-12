CREATE TABLE nato (
	alpha VARCHAR(8),
	bravo VARCHAR(8),
	second_id INT,
	PRIMARY KEY (alpha, bravo, second_id)
);

CREATE TABLE second_table (
	id INT NOT NULL,
	charlie VARCHAR(8),
	third_id INT,
	PRIMARY KEY (id)
);

INSERT INTO second_table (id, charlie, third_id) VALUES (1, '\u03B3', 1);

CREATE TABLE third_table (
	id INT NOT NULL,
	delta VARCHAR(8),
	PRIMARY KEY (id)
);

INSERT INTO third_table (id, delta) VALUES (1, '\u03B4');
