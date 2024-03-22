CREATE TABLE Admin (
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255),
    PRIMARY KEY (username)
);

CREATE TABLE Student (
    id VARCHAR(255) NOT NULL,
    fullName VARCHAR(255),
    rollNo INT,
    email VARCHAR(255),
    mobile VARCHAR(255),
    gender VARCHAR(255),
    parentName VARCHAR(255),
    parentMobile VARCHAR(255),
    year VARCHAR(255),
    joiningyear VARCHAR(255),
    address VARCHAR(255),
    image BLOB,
    academicyear VARCHAR(255),
    password VARCHAR(255),
    role VARCHAR(255) DEFAULT 'student',
    PRIMARY KEY (id)
);

CREATE TABLE Timetable (
    id INT AUTO_INCREMENT NOT NULL,
    year VARCHAR(255),
    academicyear VARCHAR(255),
    PRIMARY KEY (id)
);

CREATE TABLE Days (
    id INT AUTO_INCREMENT NOT NULL,
    day VARCHAR(255),
    timetableId INT,
    PRIMARY KEY (id),
    FOREIGN KEY (timetableId) REFERENCES Timetable(id)
);


CREATE TABLE Periods (
    id INT AUTO_INCREMENT NOT NULL,
    daysId INT,
    time VARCHAR(255),
    subject VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (daysId) REFERENCES Days(id)
);

CREATE TABLE Attendance (
    id INT AUTO_INCREMENT NOT NULL,
    studentId VARCHAR(255),
    studentName VARCHAR(255),
    date DATETIME,
    year VARCHAR(255),
    academicyear VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (studentId) REFERENCES Student(id)
);

CREATE TABLE Subject (
    id INT AUTO_INCREMENT NOT NULL,
    attendanceId INT,
    time VARCHAR(255),
    subject VARCHAR(255),
    present BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id),
    FOREIGN KEY (attendanceId) REFERENCES Attendance(id)
);

CREATE TABLE Assessment (
    id INT AUTO_INCREMENT NOT NULL,
    studentId VARCHAR(255),
    year VARCHAR(255),
    studentName VARCHAR(255),
    name VARCHAR(255),
    status VARCHAR(255),
    academicyear VARCHAR(255),
    assessment VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (studentId) REFERENCES Student(id)
);

CREATE TABLE AssessmentSubject (
    id INT AUTO_INCREMENT NOT NULL,
    assessmentId INT,
    subject VARCHAR(255),
    theoryMarks INT,
    practicalMarks INT,
    PRIMARY KEY (id),
    FOREIGN KEY (assessmentId) REFERENCES Assessment(id)
);
