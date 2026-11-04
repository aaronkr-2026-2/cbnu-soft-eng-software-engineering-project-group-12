CREATE DATABASE IF NOT EXISTS korean_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE korean_app;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  korean VARCHAR(100) NOT NULL,
  romanization VARCHAR(100) NOT NULL,
  meaning VARCHAR(200) NOT NULL,
  example_ko VARCHAR(255) NULL,
  example_en VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_daily_words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  word_id INT UNSIGNED NOT NULL,
  study_date DATE NOT NULL,
  is_learned TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_user_word (user_id, word_id),
  KEY idx_user_date (user_id, study_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  korean VARCHAR(100) NOT NULL,
  romanization VARCHAR(100) NULL,
  meaning VARCHAR(200) NOT NULL,
  example_ko VARCHAR(255) NULL,
  example_en VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user_korean (user_id, korean),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('pdf','image') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(64) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user_kind (user_id, kind, created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO words (korean, romanization, meaning, example_ko, example_en) VALUES
('안녕하세요','annyeonghaseyo','Hello','안녕하세요, 만나서 반갑습니다.','Hello, nice to meet you.'),
('감사합니다','gamsahamnida','Thank you','도와주셔서 감사합니다.','Thank you for helping me.'),
('네','ne','Yes','네, 알겠어요.','Yes, I understand.'),
('아니요','aniyo','No','아니요, 괜찮아요.','No, it is okay.'),
('물','mul','Water','물 좀 주세요.','Please give me some water.'),
('밥','bap','Rice / Meal','밥 먹었어요?','Have you eaten?'),
('사람','saram','Person','저 사람은 누구예요?','Who is that person?'),
('집','jip','House / Home','집에 가고 싶어요.','I want to go home.'),
('학교','hakgyo','School','학교에 갑니다.','I go to school.'),
('친구','chingu','Friend','친구를 만났어요.','I met a friend.'),
('사랑','sarang','Love','사랑해요.','I love you.'),
('시간','sigan','Time','시간이 없어요.','I have no time.'),
('오늘','oneul','Today','오늘은 날씨가 좋아요.','The weather is nice today.'),
('내일','naeil','Tomorrow','내일 만나요.','See you tomorrow.'),
('어제','eoje','Yesterday','어제 영화를 봤어요.','I watched a movie yesterday.'),
('책','chaek','Book','책을 읽어요.','I read a book.'),
('음식','eumsik','Food','한국 음식을 좋아해요.','I like Korean food.'),
('커피','keopi','Coffee','커피 한 잔 주세요.','One coffee please.'),
('돈','don','Money','돈이 필요해요.','I need money.'),
('일','il','Work / Task','일이 많아요.','I have a lot of work.'),
('가다','gada','To go','학교에 가요.','I go to school.'),
('오다','oda','To come','친구가 와요.','My friend is coming.'),
('먹다','meokda','To eat','저는 김치를 먹어요.','I eat kimchi.'),
('마시다','masida','To drink','물을 마셔요.','I drink water.'),
('보다','boda','To see / watch','텔레비전을 봐요.','I watch TV.'),
('좋다','jota','Good / To like','날씨가 좋아요.','The weather is good.'),
('크다','keuda','Big','집이 커요.','The house is big.'),
('작다','jakda','Small','가방이 작아요.','The bag is small.'),
('예쁘다','yeppeuda','Pretty','꽃이 예뻐요.','The flower is pretty.'),
('맛있다','masitda','Delicious','정말 맛있어요.','It is really delicious.');
