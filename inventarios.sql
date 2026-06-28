CREATE TABLE aperturas_inventario (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa BIGINT UNSIGNED NOT NULL,
    sucursal BIGINT UNSIGNED NOT NULL,
    user BIGINT UNSIGNED NULL,
    fecha_apertura DATE NOT NULL,
    hora_apertura TIME NOT NULL,
    fecha_cierre DATE NULL,
    hora_cierre TIME NULL,
    responsable VARCHAR(150) NULL,
    estado ENUM('Abierto','Cerrado') DEFAULT 'Abierto',
    observacion TEXT NULL,
    total_sistema decimal(20,4) NULL,
    total_fisico decimal(20,4) NULL,
    total_diferencia decimal(20,4) NULL,
    sincro_id CHAR(36) NULL,
    id_vps BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    -- Índices

    INDEX idx_empresa (empresa),
    INDEX idx_sucursal (sucursal),
    INDEX idx_user (user),
    INDEX idx_id_vps (id_vps),

    -- Relaciones
    CONSTRAINT fk_apertura_empresa
        FOREIGN KEY (empresa) REFERENCES empresas(id),

    CONSTRAINT fk_apertura_sucursal
        FOREIGN KEY (sucursal) REFERENCES sucursales(id),

    CONSTRAINT fk_apertura_user
        FOREIGN KEY (user) REFERENCES users(id)
);


ALTER TABLE hoja_inventarios
ADD COLUMN apertura_id BIGINT UNSIGNED NULL AFTER id,
ADD COLUMN fecha_inicio DATE,
ADD COLUMN hora_incio TIME,
ADD COLUMN fecha_fin DATE,
ADD COLUMN hora_fin TIME,
ADD COLUMN correlativo VARCHAR(20) NULL,
ADD COLUMN id_vps BIGINT UNSIGNED NULL,
ADD INDEX idx_apertura_id (apertura_id),
ADD CONSTRAINT fk_hoja_apertura
    FOREIGN KEY (apertura_id)
    REFERENCES aperturas_inventario(id);



ALTER TABLE hoja_inventario_detalles
ADD COLUMN id_vps BIGINT UNSIGNED NULL,
ADD COLUmn deleted_at TIMESTAMP NULL ;

ALTER TABLE users ADD COLUMN id_vps BIGINT NULL;
CREATE INDEX idx_users_id_vps ON users(id_vps);

ALTER TABLE TMP_HOJA_INVENTARIOS
ADD COLUMN cantidad decimal(10,2) NULL,
add column limit    decimal(10,2) NULL;