-- Criar banco de dados Laravel
CREATE DATABASE IF NOT EXISTS `laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar banco de dados WordPress
CREATE DATABASE IF NOT EXISTS `wordpress` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário com permissões
CREATE USER IF NOT EXISTS 'sail'@'%' IDENTIFIED BY 'password';

-- Conceder permissões
GRANT ALL PRIVILEGES ON `laravel`.* TO 'sail'@'%';
GRANT ALL PRIVILEGES ON `wordpress`.* TO 'sail'@'%';

-- Aplicar mudanças
FLUSH PRIVILEGES;

-- Log de confirmação
SELECT 'Databases laravel and wordpress created successfully!' as Status;
