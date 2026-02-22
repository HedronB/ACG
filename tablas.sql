ALTER DATABASE u372417318_metodo_acg
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

use u372417318_metodo_acg;

CREATE TABLE roles (
    ro_id INT PRIMARY KEY,
    ro_nombre VARCHAR(20)
);

CREATE TABLE empresas (
    em_id INT AUTO_INCREMENT PRIMARY KEY,
    em_nombre VARCHAR(255) NOT NULL
);

CREATE TABLE usuarios (
    us_id INT AUTO_INCREMENT PRIMARY KEY,
    us_nombre VARCHAR(255) NOT NULL,
    us_correo VARCHAR(255) NOT NULL UNIQUE,
    us_password VARCHAR(255) NOT NULL,
    us_rol INT DEFAULT 0,
    FOREIGN KEY (us_rol) REFERENCES roles(ro_id),
    us_empresa INT DEFAULT NULL,
    FOREIGN KEY (us_empresa) REFERENCES empresas(em_id)
);

CREATE TABLE maquinas (
    ma_id INT AUTO_INCREMENT PRIMARY KEY,
    ma_usuario INT NOT NULL,
    FOREIGN KEY (ma_usuario) REFERENCES usuarios(us_id),
    ma_empresa INT NOT NULL,
    FOREIGN KEY (ma_empresa) REFERENCES empresas(em_id),
    ma_fecha DATETIME NOT NULL,
    ma_marca VARCHAR(255),
    ma_modelo VARCHAR(255),
    ma_fecha_fabr DATETIME,
    ma_ubicacion VARCHAR(255),
    ma_tipo DECIMAL(10,2),
    ma_ancho DECIMAL(10,2),
    ma_largo DECIMAL(10,2),
    ma_alto DECIMAL(10,2),
    ma_peso DECIMAL(10,2),
    ma_vol_tanq_aceite DECIMAL(10,2),
    ma_tonelaje DECIMAL(10,2),
    ma_dist_barras DECIMAL(10,2),
    ma_tam_platina DECIMAL(10,2),
    ma_anillo_centr DECIMAL(10,2),
    ma_alt_max_molde DECIMAL(10,2),
    ma_apert_max DECIMAL(10,2),
    ma_alt_min_molde DECIMAL(10,2),
    ma_tipo_sujecion VARCHAR(255),
    ma_molde_chico DECIMAL(10,2),
    ma_botado_patron VARCHAR(255),
    ma_botado_fuerza DECIMAL(10,2),
    ma_botado_carrera DECIMAL(10,2),
    ma_tam_unid_inyec DECIMAL(10,2),
    ma_vol_inyec DECIMAL(10,2),
    ma_diam_husillo DECIMAL(10,2),
    ma_carga_max DECIMAL(10,2),
    ma_ld VARCHAR(255),
    ma_tipo_husillo VARCHAR(255),
    ma_max_pres_inyec DECIMAL(10,2),
    ma_max_contrapres DECIMAL(10,2),
    ma_max_revol DECIMAL(10,2),
    ma_max_vel_inyec DECIMAL(10,2),
    ma_valv_shut_off VARCHAR(255),
    ma_carga_vuelo VARCHAR(255),
    ma_fuerza_apoyo DECIMAL(10,2),
    ma_noyos INT,
    ma_no_valv_aire INT,
    ma_tipo_valv_aire VARCHAR(255),
    ma_secador VARCHAR(255),
    ma_termoreguladores INT,
    ma_cargador VARCHAR(255),
    ma_canal_caliente INT,
    ma_robot VARCHAR(255),
    ma_acumul_hidr VARCHAR(255),
    ma_voltaje DECIMAL(10,2),
    ma_calentamiento DECIMAL(10,2),
    ma_tam_motor_1 DECIMAL(10,2),
    ma_tam_motor_2 DECIMAL(10,2)
);

CREATE TABLE moldes (
    mo_id INT AUTO_INCREMENT PRIMARY KEY,
    mo_usuario INT NOT NULL,
    FOREIGN KEY (mo_usuario) REFERENCES usuarios(us_id),
    mo_empresa INT NOT NULL,
    FOREIGN KEY (mo_empresa) REFERENCES empresas(em_id),    
    mo_fecha DATETIME NOT NULL,
    mo_no_pieza VARCHAR(255),
    mo_numero VARCHAR(255),
    mo_ancho DECIMAL(10,2),
    mo_alto DECIMAL(10,2),
    mo_largo DECIMAL(10,2),
    mo_placas_voladas DECIMAL(10,2),
    mo_anillo_centrador DECIMAL(10,2),
    mo_no_circ_agua INT,
    mo_peso DECIMAL(10,2),
    mo_apert_min DECIMAL(10,2),
    mo_abierto DECIMAL(10,2),
    mo_tipo_colada VARCHAR(255),
    mo_no_zonas INT,
    mo_no_cavidades INT,
    mo_peso_pieza DECIMAL(10,2),
    mo_puert_cavidad INT,
    mo_no_coladas INT,
    mo_peso_colada DECIMAL(10,2),
    mo_peso_disparo DECIMAL(10,2),
    mo_noyos VARCHAR(255),
    mo_entr_aire VARCHAR(255),
    mo_thermoreguladores VARCHAR(255),
    mo_valve_gates VARCHAR(255),
    mo_tiempo_ciclo VARCHAR(255),
    mo_cavidades_activas VARCHAR(255)
);

CREATE TABLE piezas (
    pi_id INT AUTO_INCREMENT PRIMARY KEY,
    pi_usuario INT NOT NULL,
    FOREIGN KEY (pi_usuario) REFERENCES usuarios(us_id),
    pi_empresa INT NOT NULL,
    FOREIGN KEY (pi_empresa) REFERENCES empresas(em_id),
    pi_fecha DATETIME NOT NULL,
    pi_cod_prod VARCHAR(255),
    pi_molde VARCHAR(255),
    pi_descripcion VARCHAR(255),
    pi_resina VARCHAR(255),
    pi_espesor VARCHAR(255),
    pi_area_proy VARCHAR(255),
    pi_color VARCHAR(255),
    pi_tipo_empaque VARCHAR(255),
    pi_piezas VARCHAR(255),
    pi_caja_no_pzs INT,
    pi_caja_tamaño DECIMAL(10,2),
    pi_bolsa1 VARCHAR(255),
    pi_bolsa2 VARCHAR(255),
    pi_tarima_no_cajas INT
);

CREATE TABLE resinas (
    re_id INT AUTO_INCREMENT PRIMARY KEY,
    re_usuario INT NOT NULL,
    FOREIGN KEY (re_usuario) REFERENCES usuarios(us_id),
    re_empresa INT NOT NULL,
    FOREIGN KEY (re_empresa) REFERENCES empresas(em_id),
    re_fecha DATETIME NOT NULL,
    re_cod_int VARCHAR(255),
    re_tipo_resina VARCHAR(255),
    re_grado VARCHAR(255),
    re_porc_reciclado DECIMAL(10,2),
    re_temp_masa_max DECIMAL(10,2),
    re_temp_masa_min DECIMAL(10,2),
    re_temp_ref_max DECIMAL(10,2),
    re_temp_ref_min DECIMAL(10,2),
    re_sec_temp DECIMAL(10,2),
    re_sec_tiempo DECIMAL(10,2),
    re_densidad DECIMAL(10,2),
    re_factor_correccion DECIMAL(10,2),
    re_carga DECIMAL(10,2)
);

INSERT INTO roles VALUES
(0, 'Inactivo'),
(1, 'Administrador'),
(2, 'Gerente'),
(3, 'Empleado');

INSERT INTO empresas (em_nombre) VALUES
('Tim Hortons');

INSERT INTO usuarios (us_nombre, us_correo, us_password, us_rol, us_empresa) VALUES
('Bryan García', 'bg3-fow@hotmail.com', 'secreto1', 1, 1);

CREATE TABLE plantas (
    pl_id INT AUTO_INCREMENT PRIMARY KEY,
    pl_nombre VARCHAR(255) NOT NULL,
    pl_empresa INT NOT NULL,
    pl_activo TINYINT(1) DEFAULT 1,
    pl_fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pl_empresa) REFERENCES empresas(em_id)
);

ALTER TABLE maquinas
ADD COLUMN ma_planta INT NULL AFTER ma_empresa,
ADD COLUMN activo TINYINT(1) DEFAULT 1,
ADD COLUMN updated_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD CONSTRAINT fk_maquina_planta FOREIGN KEY (ma_planta) REFERENCES plantas(pl_id),
ADD CONSTRAINT fk_maquina_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(us_id);

ALTER TABLE moldes
ADD COLUMN mo_planta INT NULL AFTER mo_empresa,
ADD COLUMN activo TINYINT(1) DEFAULT 1,
ADD COLUMN updated_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD CONSTRAINT fk_molde_planta FOREIGN KEY (mo_planta) REFERENCES plantas(pl_id),
ADD CONSTRAINT fk_molde_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(us_id);

ALTER TABLE piezas
ADD COLUMN pi_planta INT NULL AFTER pi_empresa,
ADD COLUMN activo TINYINT(1) DEFAULT 1,
ADD COLUMN updated_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD CONSTRAINT fk_pieza_planta FOREIGN KEY (pi_planta) REFERENCES plantas(pl_id),
ADD CONSTRAINT fk_pieza_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(us_id);

ALTER TABLE resinas
ADD COLUMN re_planta INT NULL AFTER re_empresa,
ADD COLUMN activo TINYINT(1) DEFAULT 1,
ADD COLUMN updated_at DATETIME NULL,
ADD COLUMN updated_by INT NULL,
ADD CONSTRAINT fk_resina_planta FOREIGN KEY (re_planta) REFERENCES plantas(pl_id),
ADD CONSTRAINT fk_resina_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(us_id);


ALTER TABLE maquinas
MODIFY COLUMN ma_tipo VARCHAR(100);

ALTER TABLE maquinas
DROP FOREIGN KEY fk_maquina_updated_by;

ALTER TABLE maquinas
DROP COLUMN activo,
DROP COLUMN updated_at,
DROP COLUMN updated_by;

ALTER TABLE maquinas
ADD COLUMN ma_activo TINYINT(1) DEFAULT 1 AFTER ma_planta,
ADD COLUMN ma_actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN ma_actualizado_por INT NULL,
ADD CONSTRAINT fk_maquina_actualizado_por 
FOREIGN KEY (ma_actualizado_por) REFERENCES usuarios(us_id);

ALTER TABLE maquinas
ADD INDEX idx_ma_empresa (ma_empresa),
ADD INDEX idx_ma_planta (ma_planta),
ADD INDEX idx_ma_usuario (ma_usuario),
ADD INDEX idx_ma_activo (ma_activo);

ALTER TABLE usuarios
ADD COLUMN us_planta INT NULL,
ADD CONSTRAINT fk_usuario_planta 
    FOREIGN KEY (us_planta) REFERENCES plantas(pl_id);

-- ============================================================
-- ACG v4 – Migración final corregida para MariaDB
-- Estado actual: us_planta ya aplicado, *_planta ya existen,
--                moldes/piezas/resinas aún con activo/updated_at/updated_by
-- ============================================================

-- ── MOLDES ───────────────────────────────────────────────────

-- 1. Quitar FK que referencia updated_by (bloquea el CHANGE)
ALTER TABLE moldes
    DROP FOREIGN KEY fk_molde_updated_by;

-- 2. Renombrar columnas de auditoría
ALTER TABLE moldes
    CHANGE COLUMN activo      mo_activo          TINYINT(1) NOT NULL DEFAULT 1,
    CHANGE COLUMN updated_at  mo_actualizado_en  DATETIME NULL,
    CHANGE COLUMN updated_by  mo_actualizado_por INT NULL;

-- 3. Recrear FK con el nuevo nombre de columna
ALTER TABLE moldes
    ADD CONSTRAINT fk_molde_actualizado_por
        FOREIGN KEY (mo_actualizado_por) REFERENCES usuarios(us_id);

-- 4. Índices
ALTER TABLE moldes
    ADD INDEX idx_mo_empresa (mo_empresa),
    ADD INDEX idx_mo_planta  (mo_planta),
    ADD INDEX idx_mo_activo  (mo_activo);


-- ── PIEZAS ───────────────────────────────────────────────────

ALTER TABLE piezas
    DROP FOREIGN KEY fk_pieza_updated_by;

ALTER TABLE piezas
    CHANGE COLUMN activo      pi_activo          TINYINT(1) NOT NULL DEFAULT 1,
    CHANGE COLUMN updated_at  pi_actualizado_en  DATETIME NULL,
    CHANGE COLUMN updated_by  pi_actualizado_por INT NULL;

-- Corregir nombre con ñ → sin ñ
-- ALTER TABLE piezas
--     CHANGE COLUMN `pi_caja_tamaño` pi_caja_tamano DECIMAL(10,2) NULL;

ALTER TABLE piezas
    ADD CONSTRAINT fk_pieza_actualizado_por
        FOREIGN KEY (pi_actualizado_por) REFERENCES usuarios(us_id);

ALTER TABLE piezas
    ADD INDEX idx_pi_empresa (pi_empresa),
    ADD INDEX idx_pi_planta  (pi_planta),
    ADD INDEX idx_pi_activo  (pi_activo);


-- ── RESINAS ──────────────────────────────────────────────────

ALTER TABLE resinas
    DROP FOREIGN KEY fk_resina_updated_by;

ALTER TABLE resinas
    CHANGE COLUMN activo      re_activo          TINYINT(1) NOT NULL DEFAULT 1,
    CHANGE COLUMN updated_at  re_actualizado_en  DATETIME NULL,
    CHANGE COLUMN updated_by  re_actualizado_por INT NULL;

ALTER TABLE resinas
    ADD CONSTRAINT fk_resina_actualizado_por
        FOREIGN KEY (re_actualizado_por) REFERENCES usuarios(us_id);

ALTER TABLE resinas
    ADD INDEX idx_re_empresa (re_empresa),
    ADD INDEX idx_re_planta  (re_planta),
    ADD INDEX idx_re_activo  (re_activo);

-- ── FIN ──────────────────────────────────────────────────────
