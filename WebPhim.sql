-- Xóa database cũ nếu tồn tại và tạo mới
DROP DATABASE IF EXISTS webphimm;
CREATE DATABASE IF NOT EXISTS webphimm;
USE webphimm;

-- Tạo bảng genres
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng countries
CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng actors
CREATE TABLE IF NOT EXISTS actors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    birth_date DATE,
    nationality VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng movies
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    video_path VARCHAR(255),
    description TEXT,
    release_year INT,
    status ENUM('Đang chiếu', 'Hoàn thành', 'Sắp chiếu') DEFAULT 'Hoàn thành',
    language VARCHAR(50),
    duration VARCHAR(50),
    ticket_price DECIMAL(10,2) DEFAULT 50000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_release_year (release_year)
);

-- Tạo bảng rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_room VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 50,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);

-- Tạo bảng seats
CREATE TABLE IF NOT EXISTS seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    status ENUM('available', 'booked') DEFAULT 'available',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE (room_id, seat_number)
);

-- Tạo bảng movie_genres
CREATE TABLE IF NOT EXISTS movie_genres (
    movie_id INT,
    genre_id INT,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

-- Tạo bảng movie_countries
CREATE TABLE IF NOT EXISTS movie_countries (
    movie_id INT,
    country_id INT,
    PRIMARY KEY (movie_id, country_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
);

-- Tạo bảng movie_actors
CREATE TABLE IF NOT EXISTS movie_actors (
    movie_id INT,
    actor_id INT,
    PRIMARY KEY (movie_id, actor_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES actors(id) ON DELETE CASCADE
);

-- Tạo bảng ratings
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_user_movie (user_id, movie_id)
);

-- Tạo bảng bookmarks
CREATE TABLE IF NOT EXISTS bookmarks (
    user_id INT,
    movie_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Tạo bảng comments
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_movie (movie_id)
);

-- Tạo bảng services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng room_detail
CREATE TABLE IF NOT EXISTS room_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_show (movie_id, room_id, date, time),
    INDEX idx_date_time (date, time),
    INDEX idx_movie_date (movie_id, date)
);

-- Tạo bảng orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    payment_memo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_movie_date_time (movie_id, date, time)
);

-- Tạo bảng order_seats
CREATE TABLE IF NOT EXISTS order_seats (
    order_id INT,
    seat_id INT,
    PRIMARY KEY (order_id, seat_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
);

-- Tạo bảng tickets
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    ticket_number INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_ticket (order_id, ticket_number)
);

-- Tạo bảng order_services
CREATE TABLE IF NOT EXISTS order_services (
    order_id INT,
    service_id INT,
    quantity INT NOT NULL CHECK (quantity > 0),
    PRIMARY KEY (order_id, service_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Tạo bảng watch_history
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watch (user_id, movie_id),
    INDEX idx_user (user_id)
);

-- Tạo bảng transactions
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    vnp_txn_ref VARCHAR(255),
    vnp_transaction_no VARCHAR(50),
    vnp_amount DECIMAL(15,2),
    vnp_response_code VARCHAR(10),
    momo_txn_ref VARCHAR(255),
    momo_trans_id VARCHAR(50),
    momo_amount DECIMAL(15,2),
    momo_result_code VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Tạo bảng deleted_orders_log để ghi log các đơn hàng bị xóa
CREATE TABLE IF NOT EXISTS deleted_orders_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    movie_title VARCHAR(255) NOT NULL,
    show_time DATETIME NOT NULL,
    room_name VARCHAR(50) NOT NULL,
    seat_numbers TEXT NOT NULL,
    services TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL CHECK (total_amount >= 0),
    payment_status VARCHAR(50) NOT NULL,
    transaction_ref VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Chèn dữ liệu vào bảng genres
INSERT INTO genres (name) VALUES 
('Hành Động'), 
('Tình Cảm'), 
('Hài Hước'), 
('Kinh Dị'), 
('Viễn Tưởng'), 
('Cổ Trang'), 
('Tâm Lý'), 
('Lãng Mạn');

-- Chèn dữ liệu vào bảng countries
INSERT INTO countries (name) VALUES 
('Việt Nam'), 
('Trung Quốc'), 
('Hàn Quốc'), 
('Nhật Bản'), 
('Mỹ'), 
('Thái Lan'),
('New Zealand'),
('Anh');

-- Chèn dữ liệu vào bảng actors
INSERT INTO actors (name, birth_date, nationality) VALUES 
('Leonardo DiCaprio', '1974-11-11', 'Mỹ'),
('Tom Hanks', '1956-07-09', 'Mỹ'),
('Brad Pitt', '1963-12-18', 'Mỹ'),
('Scarlett Johansson', '1984-11-22', 'Mỹ'),
('Christian Bale', '1974-01-30', 'Anh'),
('Natalie Portman', '1981-06-09', 'Mỹ'),
('Keanu Reeves', '1964-09-02', 'Canada'),
('Margot Robbie', '1990-07-02', 'Úc'),
('Robert Downey Jr.', '1965-04-04', 'Mỹ'),
('Emma Watson', '1990-04-15', 'Anh'),
('Chris Hemsworth', '1983-08-11', 'Úc'),
('Anne Hathaway', '1982-11-12', 'Mỹ'),
('Hugh Jackman', '1968-10-12', 'Úc'),
('Johnny Depp', '1963-06-09', 'Mỹ'),
('Zendaya', '1996-09-01', 'Mỹ'),
('Timothée Chalamet', '1995-12-27', 'Mỹ'),
('Song Kang-ho', '1967-01-17', 'Hàn Quốc'),
('Russell Crowe', '1964-04-07', 'New Zealand'),
('Mark Hamill', '1951-09-25', 'Mỹ'),
('Viggo Mortensen', '1958-10-20', 'Mỹ'),
('Jodie Foster', '1962-11-19', 'Mỹ'),
('Ryan Gosling', '1980-11-12', 'Canada'),
('Emma Stone', '1988-11-06', 'Mỹ');

-- Chèn dữ liệu vào bảng users
INSERT INTO users (username, password, email, avatar, role) VALUES 
('user1', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user1@example.com', 'uploads/avatars/user1.jpg', 'user'),
('admin', '$2y$10$EF8.bvxlNMdgQd7P7k.rr.8/m9bNj7BXDw1NW5lFgLkw3lIZFj3bu', 'admin@example.com', 'avatars/admin.jpg', 'admin'),
('user2', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user2@example.com', 'avatars/user2.jpg', 'user'),
('user3', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user3@example.com', 'avatars/user3.jpg', 'user'),
('user4', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user4@example.com', 'avatars/user4.jpg', 'user'),
('user5', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user5@example.com', 'avatars/user5.jpg', 'user'),
('user6', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user6@example.com', 'avatars/user6.jpg', 'user'),
('user7', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user7@example.com', 'avatars/user7.jpg', 'user'),
('user8', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user8@example.com', 'avatars/user8.jpg', 'user'),
('user9', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user9@example.com', 'avatars/user9.jpg', 'user'),
('user10', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user10@example.com', 'avatars/user10.jpg', 'user'),
('user11', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user11@example.com', 'avatars/user11.jpg', 'user'),
('user12', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user12@example.com', 'avatars/user12.jpg', 'user'),
('user13', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user13@example.com', 'avatars/user13.jpg', 'user'),
('user14', '$2y$10$mE9BESwHREahqrUp46Zj5utR3bFHMcsUxBdSsVvF3XSLK8bo4y8vO', 'user14@example.com', 'avatars/user14.jpg', 'user');

-- Chèn dữ liệu vào bảng movies
INSERT INTO movies (title, thumbnail, video_path, description, release_year, status, language, duration, ticket_price) VALUES 
('Hành Tinh Cát', 'img/Hành Tinh Cát.jpg', 'https://www.youtube.com/embed/8g18jFHCLXk', 'Một bộ phim viễn tưởng sử thi về cuộc chiến trên sa mạc.', 2021, 'Hoàn thành', 'Vietsub + Thuyết Minh', '155 phút', 70000),
('Nhật Ký Tự Do', 'img/Nhật Ký Tự Do.jpg', 'https://www.youtube.com/embed/NKmGVE85GUU', 'Câu chuyện cảm động về tình bạn trong tù.', 1994, 'Hoàn thành', 'Vietsub + Thuyết Minh', '142 phút', 60000),
('Kẻ Hủy Diệt', 'img/Kẻ Hủy Diệt.jpg', 'https://www.youtube.com/embed/k64P4l2Wmeg', 'Phim hành động kinh điển về robot đến từ tương lai.', 1984, 'Hoàn thành', 'Vietsub + Thuyết Minh', '107 phút', 55000),
('Titanic', 'img/Titanic.jpg', 'https://www.youtube.com/embed/2A_518UsB9g', 'Câu chuyện tình yêu định mệnh trên con tàu định mệnh.', 1997, 'Hoàn thành', 'Vietsub', '194 phút', 65000),
('Inception', 'img/Inception.jpg', 'https://www.youtube.com/embed/YoHD9XEInc0', 'Hành trình trong giấc mơ và thực tại.', 2010, 'Hoàn thành', 'Vietsub + Thuyết Minh', '148 phút', 60000),
('The Dark Knight', 'img/The Dark Knight.jpg', 'https://www.youtube.com/embed/EXeTwQWrcwY', 'Cuộc chiến giữa Batman và Joker.', 2008, 'Hoàn thành', 'Vietsub + Thuyết Minh', '152 phút', 65000),
('Avatar', 'img/Avatar.jpg', 'https://www.youtube.com/embed/5PSNL1qE6VY', 'Cuộc chiến bảo vệ Pandora.', 2009, 'Hoàn thành', 'Vietsub', '162 phút', 70000),
('The Godfather', 'img/The Godfather.jpg', 'https://www.youtube.com/embed/sY1S34973zA', 'Câu chuyện về gia đình mafia Corleone.', 1972, 'Hoàn thành', 'Vietsub', '175 phút', 60000),
('Forrest Gump', 'img/Forrest Gump.jpg', 'https://www.youtube.com/embed/bLvqoHBptjg', 'Hành trình cuộc đời của một người đàn ông đặc biệt.', 1994, 'Hoàn thành', 'Vietsub + Thuyết Minh', '142 phút', 60000),
('The Matrix', 'img/The Matrix.jpg', 'https://www.youtube.com/embed/m8e-FF8MsqU', 'Cuộc chiến giữa con người và máy móc.', 1999, 'Hoàn thành', 'Vietsub + Thuyết Minh', '136 phút', 60000),
('Interstellar', 'img/Interstellar.jpg', 'https://www.youtube.com/embed/zSWdZVtXT7E', 'Hành trình tìm kiếm ngôi nhà mới cho nhân loại.', 2014, 'Hoàn thành', 'Vietsub + Thuyết Minh', '169 phút', 65000),
('Parasite', 'img/Parasite.jpg', 'https://www.youtube.com/embed/5xH0HfJHsaY', 'Câu chuyện về bất bình đẳng xã hội.', 2019, 'Hoàn thành', 'Vietsub + Thuyết Minh', '132 phút', 65000),
('Gladiator', 'img/Gladiator.jpg', 'https://www.youtube.com/embed/owK1qxDselE', 'Hành trình trả thù của một đấu sĩ La Mã.', 2000, 'Hoàn thành', 'Vietsub', '155 phút', 60000),
('The Lion King', 'img/The Lion King.jpg', 'https://www.youtube.com/embed/7TavVZMewpY', 'Hành trình trưởng thành của Simba.', 1994, 'Hoàn thành', 'Vietsub', '88 phút', 55000),
('Pulp Fiction', 'img/Pulp Fiction.jpg', 'https://www.youtube.com/embed/s7EdQ4FqbhY', 'Những câu chuyện đan xen đầy kịch tính.', 1994, 'Hoàn thành', 'Vietsub', '154 phút', 60000),
('Fight Club', 'img/Fight Club.jpg', 'https://www.youtube.com/embed/SUXWAEX2jlg', 'Cuộc sống của những người đàn ông bất mãn.', 1999, 'Hoàn thành', 'Vietsub', '139 phút', 60000),
('The Avengers', 'img/The Avengers.jpg', 'https://www.youtube.com/embed/eOrNdBpGMv8', 'Liên minh siêu anh hùng chống lại Loki.', 2012, 'Hoàn thành', 'Vietsub + Thuyết Minh', '143 phút', 65000),
('Jurassic Park', 'img/Jurassic Park.jpg', 'https://www.youtube.com/embed/lc0UehYemQA', 'Công viên khủng long ngoài tầm kiểm soát.', 1993, 'Hoàn thành', 'Vietsub', '127 phút', 60000),
('Star Wars: Episode IV', 'img/Star Wars.jpg', 'https://www.youtube.com/embed/vZ734NRnAHA', 'Cuộc chiến giữa phiến quân và Đế quốc.', 1977, 'Hoàn thành', 'Vietsub', '121 phút', 55000),
('The Lord of the Rings: The Fellowship of the Ring', 'img/The Lord of the Rings.jpg', 'https://www.youtube.com/embed/V75dMMIW2B4', 'Hành trình tiêu diệt chiếc nhẫn quyền lực.', 2001, 'Hoàn thành', 'Vietsub', '178 phút', 65000),
('Mad Max: Fury Road', 'img/Mad Max Fury Road.jpg', 'https://www.youtube.com/embed/hEJnMQG9ev8', 'Cuộc rượt đuổi trên sa mạc hậu tận thế.', 2015, 'Hoàn thành', 'Vietsub', '120 phút', 60000),
('The Silence of the Lambs', 'img/The Silence of the Lambs.jpg', 'https://www.youtube.com/embed/RuX2MQeb8UM', 'Cuộc truy lùng kẻ ăn thịt người.', 1991, 'Hoàn thành', 'Vietsub', '118 phút', 55000),
('Schindler’s List', 'img/Schindler’s List.jpg', 'https://www.youtube.com/embed/gG22XNht7QU', 'Câu chuyện cứu người trong Thế chiến II.', 1993, 'Hoàn thành', 'Vietsub', '195 phút', 60000),
('The Departed', 'img/The Departed.jpg', 'https://www.youtube.com/embed/SGWvwj3P5YU', 'Cuộc đấu trí giữa cảnh sát và mafia.', 2006, 'Hoàn thành', 'Vietsub', '151 phút', 60000),
('No Country for Old Men', 'img/No Country for Old Men.jpg', 'https://www.youtube.com/embed/38A__WT3-o0', 'Cuộc truy đuổi vali tiền đầy căng thẳng.', 2007, 'Hoàn thành', 'Vietsub', '122 phút', 55000),
('Blade Runner', 'img/Blade Runner.jpg', 'https://www.youtube.com/embed/eogpIG53c94', 'Thế giới tương lai với người nhân bản.', 1982, 'Hoàn thành', 'Vietsub', '117 phút', 55000),
('The Empire Strikes Back', 'img/The Empire Strikes Back.jpg', 'https://www.youtube.com/embed/JNwNXF9Y6kY', 'Phần tiếp theo của Star Wars.', 1980, 'Hoàn thành', 'Vietsub', '124 phút', 55000),
('The Revenant', 'img/The Revenant.jpg', 'https://www.youtube.com/embed/LoebZZ8K5N0', 'Hành trình sinh tồn giữa thiên nhiên khắc nghiệt.', 2015, 'Hoàn thành', 'Vietsub', '156 phút', 60000),
('Jaws', 'img/Jaws.jpg', 'https://www.youtube.com/embed/U1fu_sN5LhE', 'Nỗi kinh hoàng từ cá mập khổng lồ.', 1975, 'Hoàn thành', 'Vietsub', '124 phút', 55000),
('The Exorcist', 'img/The Exorcist.jpg', 'https://www.youtube.com/embed/YDGw1MTEe9k', 'Câu chuyện trừ tà kinh điển.', 1973, 'Hoàn thành', 'Vietsub', '122 phút', 55000),
('Alien', 'img/Alien.jpg', 'https://www.youtube.com/embed/LjLamj-b0I8', 'Nỗi sợ từ sinh vật ngoài hành tinh.', 1979, 'Hoàn thành', 'Vietsub', '117 phút', 55000),
('Die Hard', 'img/Die Hard.jpg', 'https://www.youtube.com/embed/2TQ-pOvI6Xo', 'Cuộc chiến trong tòa nhà chọc trời.', 1988, 'Hoàn thành', 'Vietsub', '132 phút', 55000),
('The Terminator 2', 'img/The Terminator 2.jpg', 'https://www.youtube.com/embed/CRRlbK5w8AE', 'Người máy trở lại bảo vệ nhân loại.', 1991, 'Hoàn thành', 'Vietsub', '137 phút', 60000),
('Back to the Future', 'img/Back to the Future.jpg', 'https://www.youtube.com/embed/qvsgGtivCgs', 'Hành trình xuyên thời gian đầy thú vị.', 1985, 'Hoàn thành', 'Vietsub', '116 phút', 55000),
('The Shining', 'img/The Shining.jpg', 'https://www.youtube.com/embed/WDpipB4yehk', 'Nỗi kinh hoàng trong khách sạn vắng vẻ.', 1980, 'Hoàn thành', 'Vietsub', '146 phút', 55000),
('Goodfellas', 'img/Goodfellas.jpg', 'https://www.youtube.com/embed/qo5jJ1qqEC8', 'Cuộc sống của một gangster.', 1990, 'Hoàn thành', 'Vietsub', '146 phút', 60000),
('Saving Private Ryan', 'img/Saving Private Ryan.jpg', 'https://www.youtube.com/embed/RYID71hYHzg', 'Nhiệm vụ giải cứu trong Thế chiến II.', 1998, 'Hoàn thành', 'Vietsub', '169 phút', 65000),
('The Wolf of Wall Street', 'img/The Wolf of Wall Street.jpg', 'https://www.youtube.com/embed/iszwuX1AK6A', 'Cuộc sống xa hoa của một nhà môi giới.', 2013, 'Hoàn thành', 'Vietsub', '180 phút', 65000),
('La La Land', 'img/La La Land.jpg', 'https://www.youtube.com/embed/0pdqf4k9ZMg', 'Câu chuyện tình yêu giữa âm nhạc và ước mơ.', 2016, 'Hoàn thành', 'Vietsub', '128 phút', 60000),
('Coco', 'img/Coco.jpg', 'https://www.youtube.com/embed/Ga6RYejo6Hk', 'Hành trình trong thế giới của người chết.', 2017, 'Hoàn thành', 'Vietsub', '105 phút', 60000),
('The Grand Budapest Hotel', 'img/The Grand Budapest Hotel.jpg', 'https://www.youtube.com/embed/1Fg5iWmQjwk', 'Câu chuyện hài hước trong một khách sạn.', 2014, 'Hoàn thành', 'Vietsub', '99 phút', 55000),
('Whiplash', 'img/Whiplash.jpg', 'https://www.youtube.com/embed/7d_jQycdQgo', 'Áp lực để trở thành nghệ sĩ trống xuất sắc.', 2014, 'Hoàn thành', 'Vietsub', '107 phút', 60000),
('The Prestige', 'img/The Prestige.jpg', 'https://www.youtube.com/embed/ijXruSzfGEc', 'Cuộc cạnh tranh giữa hai ảo thuật gia.', 2006, 'Hoàn thành', 'Vietsub', '130 phút', 60000),
('Eternal Sunshine', 'img/Eternal Sunshine.jpg', 'https://www.youtube.com/embed/0z1xkkMh2nI', 'Tình yêu và ký ức bị xóa nhòa.', 2004, 'Hoàn thành', 'Vietsub', '108 phút', 55000),
('The Truman Show', 'img/The Truman Show.jpg', 'https://www.youtube.com/embed/dlnmQbPGuls', 'Cuộc sống là một chương trình truyền hình.', 1998, 'Hoàn thành', 'Vietsub', '103 phút', 55000),
('Toy Story', 'img/Toy Story.jpg', 'https://www.youtube.com/embed/KYz2wyBy3kc', 'Cuộc phiêu lưu của các món đồ chơi.', 1995, 'Hoàn thành', 'Vietsub', '81 phút', 55000),
('Up', 'img/Up.jpg', 'https://www.youtube.com/embed/pkqzFUhGPJg', 'Hành trình bay lên bằng bóng bay.', 2009, 'Hoàn thành', 'Vietsub', '96 phút', 55000),
('Inside Out', 'img/Inside Out.jpg', 'https://www.youtube.com/embed/seMwpP0yeu4', 'Câu chuyện trong tâm trí một cô bé.', 2015, 'Hoàn thành', 'Vietsub', '95 phút', 60000),
('The Incredibles', 'img/The Incredibles.jpg', 'https://www.youtube.com/embed/eZbzbC9285I', 'Gia đình siêu anh hùng chống tội phạm.', 2004, 'Hoàn thành', 'Vietsub', '115 phút', 60000),
('Spider-Man: No Way Home', 'img/Spider-Man No Way Home.jpg', 'https://www.youtube.com/embed/JfVOs4VSpmA', 'Hành trình đa vũ trụ của Người Nhện.', 2021, 'Hoàn thành', 'Vietsub + Thuyết Minh', '148 phút', 75000),
('Dune: Part Two', 'img/Dune Part Two.jpg', 'https://www.youtube.com/embed/Way9Dexny3w', 'Phần tiếp theo của Hành Tinh Cát.', 2024, 'Hoàn thành', 'Vietsub + Thuyết Minh', '166 phút', 80000),
('Mission: Impossible – Dead Reckoning', 'img/Mission Impossible Dead Reckoning Part One.jpg', 'https://www.youtube.com/embed/avz06HGL2TY', 'Nhiệm vụ bất khả thi mới của Ethan Hunt.', 2023, 'Hoàn thành', 'Vietsub + Thuyết Minh', '163 phút', 75000),
('Barbie', 'img/Barbie.jpg', 'https://www.youtube.com/embed/pBk4NYhWNMM', 'Hành trình của Barbie trong thế giới thực.', 2023, 'Hoàn thành', 'Vietsub + Thuyết Minh', '114 phút', 70000),
('Oppenheimer', 'img/Oppenheimer.jpg', 'https://www.youtube.com/embed/bK6ldnjE3Y0', 'Câu chuyện về cha đẻ của bom nguyên tử.', 2023, 'Hoàn thành', 'Vietsub + Thuyết Minh', '180 phút', 75000),
('Deadpool & Wolverine', 'img/Deadpool & Wolverine.jpg', 'https://www.youtube.com/embed/73_1biulkYk', 'Sự hợp tác giữa Deadpool và Wolverine.', 2024, 'Hoàn thành', 'Vietsub + Thuyết Minh', '127 phút', 80000),
('Inside Out 2', 'img/Inside Out 2.jpg', 'https://www.youtube.com/embed/LEjhY15eCx0', 'Tiếp tục hành trình cảm xúc của Riley khi trưởng thành.', 2024, 'Hoàn thành', 'Vietsub + Thuyết Minh', '96 phút', 70000),
('Despicable Me 4', 'img/Despicable Me 4.jpg', 'https://www.youtube.com/embed/qQlr9-rXrCg', 'Cuộc phiêu lưu mới của Gru và các Minion.', 2024, 'Hoàn thành', 'Vietsub + Thuyết Minh', '95 phút', 65000);

-- Chèn dữ liệu vào bảng movie_genres
INSERT INTO movie_genres (movie_id, genre_id) VALUES 
(1, 5), (1, 1), -- Hành Tinh Cát: Viễn Tưởng, Hành Động
(2, 7), -- Nhật Ký Tự Do: Tâm Lý
(3, 1), (3, 5), -- Kẻ Hủy Diệt: Hành Động, Viễn Tưởng
(4, 2), (4, 8), -- Titanic: Tình Cảm, Lãng Mạn
(5, 1), (5, 5), -- Inception: Hành Động, Viễn Tưởng
(6, 1), -- The Dark Knight: Hành Động
(7, 5), -- Avatar: Viễn Tưởng
(8, 7), -- The Godfather: Tâm Lý
(9, 7), (9, 3), -- Forrest Gump: Tâm Lý, Hài Hước
(10, 1), (10, 5), -- The Matrix: Hành Động, Viễn Tưởng
(11, 5), -- Interstellar: Viễn Tưởng
(12, 7), -- Parasite: Tâm Lý
(13, 1), -- Gladiator: Hành Động
(14, 3), -- The Lion King: Hài Hước
(15, 7), -- Pulp Fiction: Tâm Lý
(16, 7), -- Fight Club: Tâm Lý
(17, 1), -- The Avengers: Hành Động
(18, 5), -- Jurassic Park: Viễn Tưởng
(19, 5), -- Star Wars: Episode IV: Viễn Tưởng
(20, 5), -- The Lord of the Rings: Viễn Tưởng
(21, 1), -- Mad Max: Fury Road: Hành Động
(22, 4), -- The Silence of the Lambs: Kinh Dị
(23, 7), -- Schindler’s List: Tâm Lý
(24, 7), -- The Departed: Tâm Lý
(25, 7), -- No Country for Old Men: Tâm Lý
(26, 5), -- Blade Runner: Viễn Tưởng
(27, 5), -- The Empire Strikes Back: Viễn Tưởng
(28, 1), -- The Revenant: Hành Động
(29, 4), -- Jaws: Kinh Dị
(30, 4), -- The Exorcist: Kinh Dị
(31, 4), (31, 5), -- Alien: Kinh Dị, Viễn Tưởng
(32, 1), -- Die Hard: Hành Động
(33, 1), (33, 5), -- The Terminator 2: Hành Động, Viễn Tưởng
(34, 5), -- Back to the Future: Viễn Tưởng
(35, 4), -- The Shining: Kinh Dị
(36, 7), -- Goodfellas: Tâm Lý
(37, 1), -- Saving Private Ryan: Hành Động
(38, 7), -- The Wolf of Wall Street: Tâm Lý
(39, 8), -- La La Land: Lãng Mạn
(40, 3), -- Coco: Hài Hước
(41, 3), -- The Grand Budapest Hotel: Hài Hước
(42, 7), -- Whiplash: Tâm Lý
(43, 7), -- The Prestige: Tâm Lý
(44, 8), -- Eternal Sunshine: Lãng Mạn
(45, 7), -- The Truman Show: Tâm Lý
(46, 3), -- Toy Story: Hài Hước
(47, 3), -- Up: Hài Hước
(48, 3), -- Inside Out: Hài Hước
(49, 1), -- The Incredibles: Hành Động
(50, 1), -- Spider-Man: No Way Home: Hành Động
(51, 5), (51, 1), -- Dune: Part Two: Viễn Tưởng, Hành Động
(52, 1), -- Mission: Impossible – Dead Reckoning: Hành Động
(53, 3), -- Barbie: Hài Hước
(54, 7), -- Oppenheimer: Tâm Lý
(55, 1), (55, 3), -- Deadpool & Wolverine: Hành Động, Hài Hước
(56, 3), -- Inside Out 2: Hài Hước
(57, 3); -- Despicable Me 4: Hài Hước

-- Chèn dữ liệu vào bảng movie_countries
INSERT INTO movie_countries (movie_id, country_id) VALUES 
(1, 5), -- Hành Tinh Cát: Mỹ
(2, 5), -- Nhật Ký Tự Do: Mỹ
(3, 5), -- Kẻ Hủy Diệt: Mỹ
(4, 5), -- Titanic: Mỹ
(5, 5), -- Inception: Mỹ
(6, 5), -- The Dark Knight: Mỹ
(7, 5), -- Avatar: Mỹ
(8, 5), -- The Godfather: Mỹ
(9, 5), -- Forrest Gump: Mỹ
(10, 5), -- The Matrix: Mỹ
(11, 5), -- Interstellar: Mỹ
(12, 3), -- Parasite: Hàn Quốc
(13, 5), -- Gladiator: Mỹ
(14, 5), -- The Lion King: Mỹ
(15, 5), -- Pulp Fiction: Mỹ
(16, 5), -- Fight Club: Mỹ
(17, 5), -- The Avengers: Mỹ
(18, 5), -- Jurassic Park: Mỹ
(19, 5), -- Star Wars: Episode IV: Mỹ
(20, 7), -- The Lord of the Rings: New Zealand
(21, 5), -- Mad Max: Fury Road: Mỹ
(22, 5), -- The Silence of the Lambs: Mỹ
(23, 5), -- Schindler’s List: Mỹ
(24, 5), -- The Departed: Mỹ
(25, 5), -- No Country for Old Men: Mỹ
(26, 5), -- Blade Runner: Mỹ
(27, 5), -- The Empire Strikes Back: Mỹ
(28, 5), -- The Revenant: Mỹ
(29, 5), -- Jaws: Mỹ
(30, 5), -- The Exorcist: Mỹ
(31, 5), -- Alien: Mỹ
(32, 5), -- Die Hard: Mỹ
(33, 5), -- The Terminator 2: Mỹ
(34, 5), -- Back to the Future: Mỹ
(35, 5), -- The Shining: Mỹ
(36, 5), -- Goodfellas: Mỹ
(37, 5), -- Saving Private Ryan: Mỹ
(38, 5), -- The Wolf of Wall Street: Mỹ
(39, 5), -- La La Land: Mỹ
(40, 5), -- Coco: Mỹ
(41, 5), -- The Grand Budapest Hotel: Mỹ
(42, 5), -- Whiplash: Mỹ
(43, 5), -- The Prestige: Mỹ
(44, 5), -- Eternal Sunshine: Mỹ
(45, 5), -- The Truman Show: Mỹ
(46, 5), -- Toy Story: Mỹ
(47, 5), -- Up: Mỹ
(48, 5), -- Inside Out: Mỹ
(49, 5), -- The Incredibles: Mỹ
(50, 5), -- Spider-Man: No Way Home: Mỹ
(51, 5), -- Dune: Part Two: Mỹ
(52, 5), -- Mission: Impossible – Dead Reckoning: Mỹ
(53, 5), -- Barbie: Mỹ
(54, 5), -- Oppenheimer: Mỹ
(55, 5), -- Deadpool & Wolverine: Mỹ
(56, 5), -- Inside Out 2: Mỹ
(57, 5); -- Despicable Me 4: Mỹ

-- Chèn dữ liệu vào bảng movie_actors
INSERT INTO movie_actors (movie_id, actor_id) VALUES 
(1, 16), (1, 15), -- Hành Tinh Cát: Timothée Chalamet, Zendaya
(2, 2), -- Nhật Ký Tự Do: Tom Hanks
(3, 7), -- Kẻ Hủy Diệt: Keanu Reeves
(4, 1), (4, 12), -- Titanic: Leonardo DiCaprio, Anne Hathaway
(5, 1), -- Inception: Leonardo DiCaprio
(6, 5), -- The Dark Knight: Christian Bale
(7, 11), -- Avatar: Chris Hemsworth
(8, 3), -- The Godfather: Brad Pitt
(9, 2), -- Forrest Gump: Tom Hanks
(10, 7), -- The Matrix: Keanu Reeves
(11, 12), -- Interstellar: Anne Hathaway
(12, 17), -- Parasite: Song Kang-ho
(13, 18), -- Gladiator: Russell Crowe
(14, 19), -- The Lion King: Mark Hamill (lồng tiếng)
(15, 14), -- Pulp Fiction: Johnny Depp
(16, 3), -- Fight Club: Brad Pitt
(17, 9), -- The Avengers: Robert Downey Jr.
(18, 2), -- Jurassic Park: Tom Hanks
(19, 19), -- Star Wars: Episode IV: Mark Hamill
(20, 20), -- The Lord of the Rings: Viggo Mortensen
(21, 11), -- Mad Max: Fury Road: Chris Hemsworth
(22, 21), -- The Silence of the Lambs: Jodie Foster
(23, 1), -- Schindler’s List: Leonardo DiCaprio
(24, 1), -- The Departed: Leonardo DiCaprio
(25, 3), -- No Country for Old Men: Brad Pitt
(26, 7), -- Blade Runner: Keanu Reeves
(27, 19), -- The Empire Strikes Back: Mark Hamill
(28, 1), -- The Revenant: Leonardo DiCaprio
(29, 2), -- Jaws: Tom Hanks
(30, 21), -- The Exorcist: Jodie Foster
(31, 21), -- Alien: Jodie Foster
(32, 14), -- Die Hard: Johnny Depp
(33, 7), -- The Terminator 2: Keanu Reeves
(34, 2), -- Back to the Future: Tom Hanks
(35, 2), -- The Shining: Tom Hanks
(36, 3), -- Goodfellas: Brad Pitt
(37, 2), -- Saving Private Ryan: Tom Hanks
(38, 1), -- The Wolf of Wall Street: Leonardo DiCaprio
(39, 22), (39, 23), -- La La Land: Ryan Gosling, Emma Stone
(40, 10), -- Coco: Emma Watson
(41, 10), -- The Grand Budapest Hotel: Emma Watson
(42, 10), -- Whiplash: Emma Watson
(43, 5), -- The Prestige: Christian Bale
(44, 12), -- Eternal Sunshine: Anne Hathaway
(45, 2), -- The Truman Show: Tom Hanks
(46, 2), -- Toy Story: Tom Hanks (lồng tiếng)
(47, 2), -- Up: Tom Hanks (lồng tiếng)
(48, 10), -- Inside Out: Emma Watson (lồng tiếng)
(49, 9), -- The Incredibles: Robert Downey Jr. (lồng tiếng)
(50, 15), -- Spider-Man: No Way Home: Zendaya
(51, 16), -- Dune: Part Two: Timothée Chalamet
(52, 2), -- Mission: Impossible – Dead Reckoning: Tom Hanks
(53, 8), -- Barbie: Margot Robbie
(54, 5), -- Oppenheimer: Christian Bale
(55, 13), -- Deadpool & Wolverine: Hugh Jackman
(56, 10), -- Inside Out 2: Emma Watson (lồng tiếng)
(57, 2); -- Despicable Me 4: Tom Hanks (lồng tiếng)

-- Chèn dữ liệu vào bảng rooms
INSERT INTO rooms (id_room, name, capacity, status) VALUES 
('R001', 'Phòng 1', 50, 'active'),
('R002', 'Phòng 2', 50, 'active'),
('R003', 'Phòng 3', 50, 'active'),
('R004', 'Phòng 4', 50, 'active'),
('R005', 'Phòng 5', 50, 'active'),
('R006', 'Phòng 6', 50, 'active'),
('R007', 'Phòng 7', 50, 'active'),
('R008', 'Phòng 8', 50, 'active'),
('R009', 'Phòng 9', 50, 'active'),
('R010', 'Phòng 10', 50, 'active'),
('R011', 'Phòng 11', 50, 'active'),
('R012', 'Phòng 12', 50, 'active');

-- Chèn dữ liệu vào bảng seats
DELIMITER //
CREATE PROCEDURE GenerateSeats()
BEGIN
    DECLARE room_counter INT DEFAULT 1;
    DECLARE row_letter CHAR(1);
    DECLARE seat_num INT;
    
    WHILE room_counter <= 12 DO
        SET row_letter = 'A';
        WHILE row_letter <= 'E' DO
            SET seat_num = 1;
            WHILE seat_num <= 10 DO
                INSERT INTO seats (room_id, seat_number)
                VALUES (room_counter, CONCAT(row_letter, LPAD(seat_num, 2, '0')));
                SET seat_num = seat_num + 1;
            END WHILE;
            SET row_letter = CHAR(ASCII(row_letter) + 1);
        END WHILE;
        SET room_counter = room_counter + 1;
    END WHILE;
END //
DELIMITER ;

CALL GenerateSeats();

-- Chèn dữ liệu vào bảng ratings
INSERT INTO ratings (user_id, movie_id, rating, comment) VALUES 
(1, 1, 5, 'Phim hay, rất đáng xem!'),
(2, 1, 4, 'Hình ảnh đẹp, nhưng hơi dài.'),
(1, 2, 5, 'Cảm động, diễn xuất tuyệt vời!'),
(3, 3, 3, 'Phim ổn, nhưng không quá ấn tượng.'),
(4, 4, 5, 'Một câu chuyện tình yêu kinh điển!'),
(5, 5, 4, 'Kịch bản thông minh, nhưng hơi khó hiểu.'),
(6, 6, 5, 'Joker quá đỉnh!'),
(7, 7, 4, 'Hình ảnh đẹp, nhưng cốt truyện hơi đơn giản.'),
(8, 8, 5, 'Kiệt tác về mafia!'),
(9, 9, 5, 'Cảm động và ý nghĩa!'),
(10, 10, 4, 'Hành động đỉnh cao, nhưng hơi cũ.');

-- Chèn dữ liệu vào bảng bookmarks
INSERT INTO bookmarks (user_id, movie_id) VALUES 
(1, 1),
(1, 2),
(2, 3),
(3, 4),
(4, 5),
(5, 6),
(6, 7),
(7, 8),
(8, 9),
(9, 10);

-- Chèn dữ liệu vào bảng comments
INSERT INTO comments (user_id, movie_id, content) VALUES 
(1, 1, 'Tôi rất thích cảnh chiến đấu trên sa mạc!'),
(2, 1, 'Phim này có phần 2 không nhỉ?'),
(1, 2, 'Cảnh cuối khiến tôi khóc luôn!'),
(3, 3, 'Keanu Reeves đóng vai này quá hợp!'),
(4, 4, 'Cảnh tàu chìm quá chân thực!'),
(5, 5, 'Xem phim này phải suy nghĩ nhiều lắm.'),
(6, 6, 'Heath Ledger xứng đáng với Oscar!'),
(7, 7, 'Tôi muốn đến Pandora một lần!'),
(8, 8, 'Phim mafia hay nhất tôi từng xem.'),
(9, 9, 'Forrest Gump thật sự truyền cảm hứng!');

-- Chèn dữ liệu vào bảng services
INSERT INTO services (name, price, image_url) VALUES 
('Bắp rang bơ (nhỏ)', 30000, 'uploads/service/popcorn_small.jpg'),
('Bắp rang bơ (lớn)', 50000, 'uploads/service/popcorn_large.jpg'),
('Nước ngọt (Pepsi)', 20000, 'uploads/service/pepsi.jpg'),
('Nước ngọt (Coca)', 20000, 'uploads/service/coca.jpg'),
('Combo 1 (Bắp nhỏ + Pepsi)', 45000, 'uploads/service/combo1.jpg'),
('Combo 2 (Bắp lớn + 2 Coca)', 80000, 'uploads/service/combo2.jpg'),
('Snack khoai tây chiên', 25000, 'uploads/service/potato_chips.jpg'),
('Kem vani', 30000, 'uploads/service/vanilla_ice_cream.jpg'),
('Nước suối', 15000, 'uploads/service/mineral_water.jpg'),
('Combo 3 (Bắp lớn + 2 Pepsi + Snack)', 95000, 'uploads/service/combo3.jpg');

-- Chèn dữ liệu vào bảng room_detail
INSERT INTO room_detail (movie_id, room_id, date, time) VALUES
(1, 1, '2025-04-24', '10:00:00'), -- Hành Tinh Cát, Phòng 1
(1, 1, '2025-04-24', '12:30:00'), -- Hành Tinh Cát, Phòng 1
(1, 2, '2025-04-25', '15:00:00'), -- Hành Tinh Cát, Phòng 2
(1, 2, '2025-04-25', '17:30:00'), -- Hành Tinh Cát, Phòng 2
(2, 3, '2025-04-24', '14:00:00'), -- Nhật Ký Tự Do, Phòng 3
(2, 3, '2025-04-24', '16:30:00'), -- Nhật Ký Tự Do, Phòng 3
(2, 4, '2025-04-26', '11:00:00'), -- Nhật Ký Tự Do, Phòng 4
(50, 5, '2025-04-25', '17:30:00'), -- Spider-Man: No Way Home, Phòng 5
(50, 5, '2025-04-25', '20:00:00'), -- Spider-Man: No Way Home, Phòng 5
(50, 6, '2025-04-27', '13:00:00'), -- Spider-Man: No Way Home, Phòng 6
(55, 7, '2025-04-25', '20:00:00'), -- Deadpool & Wolverine, Phòng 7
(55, 7, '2025-04-26', '10:00:00'), -- Deadpool & Wolverine, Phòng 7
(55, 8, '2025-04-26', '14:30:00'), -- Deadpool & Wolverine, Phòng 8
(55, 8, '2025-04-27', '16:00:00'), -- Deadpool & Wolverine, Phòng 8
(56, 9, '2025-04-26', '10:00:00'), -- Inside Out 2, Phòng 9
(56, 9, '2025-04-26', '12:00:00'), -- Inside Out 2, Phòng 9
(56, 10, '2025-04-27', '15:00:00'), -- Inside Out 2, Phòng 10
(56, 10, '2025-04-27', '17:00:00'), -- Inside Out 2, Phòng 10
(57, 11, '2025-04-24', '13:00:00'), -- Despicable Me 4, Phòng 11
(57, 11, '2025-04-24', '15:30:00'), -- Despicable Me 4, Phòng 11
(57, 12, '2025-04-25', '11:00:00'), -- Despicable Me 4, Phòng 12
(53, 1, '2025-04-26', '16:00:00'), -- Barbie, Phòng 1
(53, 1, '2025-04-26', '18:30:00'), -- Barbie, Phòng 1
(53, 2, '2025-04-27', '12:00:00'), -- Barbie, Phòng 2
(54, 3, '2025-04-25', '14:00:00'), -- Oppenheimer, Phòng 3
(54, 3, '2025-04-25', '16:30:00'), -- Oppenheimer, Phòng 3
(54, 4, '2025-04-26', '19:00:00'), -- Oppenheimer, Phòng 4
(39, 5, '2025-04-24', '11:00:00'), -- La La Land, Phòng 5
(39, 5, '2025-04-24', '13:30:00'), -- La La Land, Phòng 5
(39, 6, '2025-04-26', '15:00:00'), -- La La Land, Phòng 6
(4, 7, '2025-04-27', '10:00:00'),  -- Titanic, Phòng 7
(4, 7, '2025-04-27', '12:30:00'),  -- Titanic, Phòng 7
(4, 8, '2025-04-28', '14:00:00'),  -- Titanic, Phòng 8
(4, 7, '2025-04-30', '12:30:00'),  -- Titanic, Phòng 7
(4, 8, '2025-05-28', '14:00:00');  -- Titanic, Phòng 8

-- Chèn dữ liệu vào bảng orders
INSERT INTO orders (user_id, movie_id, room_id, date, time, quantity, total_amount, status, payment_memo) VALUES 
(1, 1, 1, '2025-04-24', '10:00:00', 3, 210000, 'completed', 'WebPhim_1_1'), -- Hành Tinh Cát, Phòng 1
(2, 1, 1, '2025-04-24', '12:30:00', 2, 140000, 'completed', 'WebPhim_2_2'), -- Hành Tinh Cát, Phòng 1
(3, 1, 2, '2025-04-25', '15:00:00', 4, 280000, 'completed', 'WebPhim_3_3'), -- Hành Tinh Cát, Phòng 2
(4, 1, 2, '2025-04-25', '17:30:00', 2, 140000, 'completed', 'WebPhim_4_4'), -- Hành Tinh Cát, Phòng 2
(5, 2, 3, '2025-04-24', '14:00:00', 1, 60000, 'completed', 'WebPhim_5_5'), -- Nhật Ký Tự Do, Phòng 3
(6, 2, 3, '2025-04-24', '16:30:00', 3, 180000, 'completed', 'WebPhim_6_6'), -- Nhật Ký Tự Do, Phòng 3
(7, 2, 4, '2025-04-26', '11:00:00', 2, 120000, 'completed', 'WebPhim_7_7'), -- Nhật Ký Tự Do, Phòng 4
(8, 50, 5, '2025-04-25', '17:30:00', 3, 225000, 'completed', 'WebPhim_8_8'), -- Spider-Man: No Way Home, Phòng 5
(9, 50, 5, '2025-04-25', '20:00:00', 2, 150000, 'completed', 'WebPhim_9_9'), -- Spider-Man: No Way Home, Phòng 5
(10, 50, 6, '2025-04-27', '13:00:00', 4, 300000, 'completed', 'WebPhim_10_10'), -- Spider-Man: No Way Home, Phòng 6
(11, 55, 7, '2025-04-25', '20:00:00', 2, 160000, 'completed', 'WebPhim_11_11'), -- Deadpool & Wolverine, Phòng 7
(12, 55, 7, '2025-04-26', '10:00:00', 3, 240000, 'completed', 'WebPhim_12_12'), -- Deadpool & Wolverine, Phòng 7
(13, 55, 8, '2025-04-26', '14:30:00', 1, 80000, 'completed', 'WebPhim_13_13'), -- Deadpool & Wolverine, Phòng 8
(14, 55, 8, '2025-04-27', '16:00:00', 2, 160000, 'completed', 'WebPhim_14_14'), -- Deadpool & Wolverine, Phòng 8
(1, 56, 9, '2025-04-26', '10:00:00', 1, 70000, 'completed', 'WebPhim_15_1'), -- Inside Out 2, Phòng 9
(2, 56, 9, '2025-04-26', '12:00:00', 3, 210000, 'completed', 'WebPhim_16_2'), -- Inside Out 2, Phòng 9
(3, 56, 10, '2025-04-27', '15:00:00', 2, 140000, 'completed', 'WebPhim_17_3'), -- Inside Out 2, Phòng 10
(4, 56, 10, '2025-04-27', '17:00:00', 2, 140000, 'completed', 'WebPhim_18_4'), -- Inside Out 2, Phòng 10
(5, 57, 11, '2025-04-24', '13:00:00', 1, 65000, 'completed', 'WebPhim_19_5'), -- Despicable Me 4, Phòng 11
(6, 57, 11, '2025-04-24', '15:30:00', 2, 130000, 'completed', 'WebPhim_20_6'), -- Despicable Me 4, Phòng 11
(7, 57, 12, '2025-04-25', '11:00:00', 3, 195000, 'completed', 'WebPhim_21_7'), -- Despicable Me 4, Phòng 12
(8, 53, 1, '2025-04-26', '16:00:00', 2, 140000, 'completed', 'WebPhim_22_8'), -- Barbie, Phòng 1
(9, 53, 1, '2025-04-26', '18:30:00', 2, 140000, 'completed', 'WebPhim_23_9'), -- Barbie, Phòng 1
(10, 53, 2, '2025-04-27', '12:00:00', 1, 70000, 'completed', 'WebPhim_24_10'), -- Barbie, Phòng 2
(11, 54, 3, '2025-04-25', '14:00:00', 2, 150000, 'completed', 'WebPhim_25_11'), -- Oppenheimer, Phòng 3
(12, 54, 3, '2025-04-25', '16:30:00', 3, 225000, 'completed', 'WebPhim_26_12'), -- Oppenheimer, Phòng 3
(13, 54, 4, '2025-04-26', '19:00:00', 1, 75000, 'completed', 'WebPhim_27_13'), -- Oppenheimer, Phòng 4
(14, 39, 5, '2025-04-24', '11:00:00', 2, 120000, 'completed', 'WebPhim_28_14'), -- La La Land, Phòng 5
(1, 39, 5, '2025-04-24', '13:30:00', 2, 120000, 'completed', 'WebPhim_29_1'), -- La La Land, Phòng 5
(2, 39, 6, '2025-04-26', '15:00:00', 3, 180000, 'completed', 'WebPhim_30_2'), -- La La Land, Phòng 6
(3, 4, 7, '2025-04-27', '10:00:00', 2, 130000, 'completed', 'WebPhim_31_3'), -- Titanic, Phòng 7
(4, 4, 7, '2025-04-27', '12:30:00', 2, 130000, 'completed', 'WebPhim_32_4'), -- Titanic, Phòng 7
(5, 4, 8, '2025-04-28', '14:00:00', 1, 65000, 'completed', 'WebPhim_33_5'); -- Titanic, Phòng 8

-- Chèn dữ liệu vào bảng order_seats
INSERT INTO order_seats (order_id, seat_id) VALUES 
(1, 1), (1, 2), (1, 3), -- A01, A02, A03 in Room 1
(2, 4), (2, 5), -- A04, A05 in Room 1
(3, 51), (3, 52), (3, 53), (3, 54), -- A01, A02, A03, A04 in Room 2
(4, 55), (4, 56), -- A05, A06 in Room 2
(5, 101), -- A01 in Room 3
(6, 102), (6, 103), (6, 104), -- A02, A03, A04 in Room 3
(7, 151), (7, 152), -- A01, A02 in Room 4
(8, 201), (8, 202), (8, 203), -- A01, A02, A03 in Room 5
(9, 204), (9, 205), -- A04, A05 in Room 5
(10, 251), (10, 252), (10, 253), (10, 254), -- A01, A02, A03, A04 in Room 6
(11, 301), (11, 302), -- A01, A02 in Room 7
(12, 303), (12, 304), (12, 305), -- A03, A04, A05 in Room 7
(13, 351), -- A01 in Room 8
(14, 352), (14, 353), -- A02, A03 in Room 8
(15, 401), -- A01 in Room 9
(16, 402), (16, 403), (16, 404), -- A02, A03, A04 in Room 9
(17, 451), (17, 452), -- A01, A02 in Room 10
(18, 453), (18, 454), -- A03, A04 in Room 10
(19, 501), -- A01 in Room 11
(20, 502), (20, 503), -- A02, A03 in Room 11
(21, 551), (21, 552), (21, 553), -- A01, A02, A03 in Room 12
(22, 6), (22, 7), -- A06, A07 in Room 1
(23, 8), (23, 9), -- A08, A09 in Room 1
(24, 57), -- A07 in Room 2
(25, 105), (25, 106), -- A05, A06 in Room 3
(26, 107), (26, 108), (26, 109), -- A07, A08, A09 in Room 3
(27, 153), -- A03 in Room 4
(28, 206), (28, 207), -- A06, A07 in Room 5
(29, 208), (29, 209), -- A08, A09 in Room 5
(30, 255), (30, 256), (30, 257), -- A05, A06, A07 in Room 6
(31, 306), (31, 307), -- A06, A07 in Room 7
(32, 308), (32, 309), -- A08, A09 in Room 7
(33, 354); -- A04 in Room 8

-- Chèn dữ liệu vào bảng tickets (tiếp tục từ điểm bị cắt)
INSERT INTO tickets (user_id, order_id, ticket_number) VALUES 
(4, 32, 1), (4, 32, 2), -- Titanic, order 32
(5, 33, 1), -- Titanic, order 33
(6, 6, 1), (6, 6, 2), (6, 6, 3), -- Nhật Ký Tự Do, order 6
(7, 7, 1), (7, 7, 2), -- Nhật Ký Tự Do, order 7
(8, 8, 1), (8, 8, 2), (8, 8, 3), -- Spider-Man: No Way Home, order 8
(9, 9, 1), (9, 9, 2), -- Spider-Man: No Way Home, order 9
(10, 10, 1), (10, 10, 2), (10, 10, 3), (10, 10, 4), -- Spider-Man: No Way Home, order 10
(11, 11, 1), (11, 11, 2), -- Deadpool & Wolverine, order 11
(12, 12, 1), (12, 12, 2), (12, 12, 3), -- Deadpool & Wolverine, order 12
(13, 13, 1), -- Deadpool & Wolverine, order 13
(14, 14, 1), (14, 14, 2), -- Deadpool & Wolverine, order 14
(1, 15, 1), -- Inside Out 2, order 15
(2, 16, 1), (2, 16, 2), (2, 16, 3), -- Inside Out 2, order 16
(3, 17, 1), (3, 17, 2), -- Inside Out 2, order 17
(4, 18, 1), (4, 18, 2), -- Inside Out 2, order 18
(5, 19, 1), -- Despicable Me 4, order 19
(6, 20, 1), (6, 20, 2), -- Despicable Me 4, order 20
(7, 21, 1), (7, 21, 2), (7, 21, 3), -- Despicable Me 4, order 21
(8, 22, 1), (8, 22, 2), -- Barbie, order 22
(9, 23, 1), (9, 23, 2), -- Barbie, order 23
(10, 24, 1), -- Barbie, order 24
(11, 25, 1), (11, 25, 2), -- Oppenheimer, order 25
(12, 26, 1), (12, 26, 2), (12, 26, 3), -- Oppenheimer, order 26
(13, 27, 1), -- Oppenheimer, order 27
(14, 28, 1), (14, 28, 2), -- La La Land, order 28
(1, 29, 1), (1, 29, 2), -- La La Land, order 29
(2, 30, 1), (2, 30, 2), (2, 30, 3), -- La La Land, order 30
(3, 31, 1), (3, 31, 2); -- Titanic, order 31

-- Chèn dữ liệu vào bảng order_services
INSERT INTO order_services (order_id, service_id, quantity) VALUES 
(1, 1, 2), -- Bắp rang bơ (nhỏ) cho order 1
(1, 3, 3), -- Pepsi cho order 1
(2, 2, 1), -- Bắp rang bơ (lớn) cho order 2
(3, 5, 2), -- Combo 1 cho order 3
(4, 4, 2), -- Coca cho order 4
(5, 9, 1), -- Nước suối cho order 5
(6, 6, 1), -- Combo 2 cho order 6
(7, 7, 2), -- Snack khoai tây chiên cho order 7
(8, 8, 3), -- Kem vani cho order 8
(9, 10, 1), -- Combo 3 cho order 9
(10, 1, 4); -- Bắp rang bơ (nhỏ) cho order 10

-- Chèn dữ liệu vào bảng watch_history
INSERT INTO watch_history (user_id, movie_id, watched_at) VALUES 
(1, 1, '2025-04-24 12:00:00'),
(2, 1, '2025-04-24 14:30:00'),
(3, 2, '2025-04-24 16:00:00'),
(4, 4, '2025-04-27 12:00:00'),
(5, 5, '2025-04-24 13:00:00'),
(6, 6, '2025-04-25 20:00:00'),
(7, 7, '2025-04-26 15:00:00'),
(8, 50, '2025-04-25 19:00:00'),
(9, 55, '2025-04-26 12:00:00'),
(10, 56, '2025-04-27 16:00:00');
