ALTER TABLE remesas

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX  idx_remesas_id_vps (id_vps);



ALTER TABLE aperturas

ADD COLUMN  id_vps BIGINT UNSIGNED NULL,

ADD INDEX  idx_aperturas_id_vps (id_vps);



ALTER TABLE cortes

ADD COLUMN  id_vps BIGINT UNSIGNED NULL,

ADD INDEX  idx_cortes_id_vps (id_vps);





ALTER TABLE productos

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_productos_id_vps (id_vps);



ALTER TABLE ventas

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_ventas_id_vps (id_vps);



ALTER TABLE ventas_detalles

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_ventas_detalles_id_vps (id_vps);



ALTER TABLE inventarios

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_inventarios_id_vps (id_vps);



ALTER TABLE kardexes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_kardexes_id_vps (id_vps);



ALTER TABLE kardexes2

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_kardexes2_id_vps (id_vps);



--Nuevos campos

ALTER TABLE compras

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_compras_id_vps (id_vps);



ALTER TABLE solicitudes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_solicitudes_id_vps (id_vps);



ALTER TABLE metas_users

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_metas_users_id_vps (id_vps);



ALTER TABLE ajustes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_ajustes_id_vps (id_vps);



ALTER TABLE solicitudes_detalles

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_solicitudesdetalles_id_vps (id_vps);



ALTER TABLE compras_detalles

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_compras_detalles_id_vps (id_vps);



ALTER TABLE ajustes_detalles

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_ajustes_detalles_id_vps (id_vps);

--- --------------------------------

ALTER TABLE cajas

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_caja_id_vps (id_vps);





ALTER TABLE dtes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_dte_id_vps (id_vps);



ALTER TABLE resumen_dtes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_resumen_dte_id_vps (id_vps);



ALTER TABLE firmadors

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_firmadors_id_vps (id_vps);



ALTER TABLE recepcion_dtes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_recepcion_dte_id_vps (id_vps);





-- Arranque 1:1 (una sola vez tras clonar del VPS)



UPDATE remesas   SET id_vps = id WHERE id_vps IS NULL;

UPDATE aperturas SET id_vps = id WHERE id_vps IS NULL;

UPDATE cortes    SET id_vps = id WHERE id_vps IS NULL;



UPDATE productos       SET id_vps = id WHERE id_vps IS NULL;

UPDATE ventas          SET id_vps = id WHERE id_vps IS NULL;

UPDATE ventas_detalles SET id_vps = id WHERE id_vps IS NULL;

UPDATE inventarios     SET id_vps = id WHERE id_vps IS NULL;

UPDATE kardexes        SET id_vps = id WHERE id_vps IS NULL;

UPDATE kardexes2        SET id_vps = id WHERE id_vps IS NULL;

--Nuevos

UPDATE compras        SET id_vps = id WHERE id_vps IS NULL;

UPDATE solicitudes    SET id_vps = id WHERE id_vps IS NULL;

UPDATE metas_users   SET id_vps = id WHERE id_vps IS NULL;

UPDATE ajustes       SET id_vps = id WHERE id_vps IS NULL;

UPDATE solicitudes_detalles SET id_vps = id WHERE id_vps IS NULL;

UPDATE compras_detalles    SET id_vps = id WHERE id_vps IS NULL;

UPDATE ajustes_detalles    SET id_vps = id WHERE id_vps IS NULL;

UPDATE cajas            SET id_vps = id WHERE id_vps IS NULL;



ALTER TABLE remesas

ADD INDEX idx_wm_remesas (updated_at, id);



ALTER TABLE aperturas

ADD INDEX idx_wm_aperturas (updated_at, id);



ALTER TABLE cortes

ADD INDEX  idx_wm_cortes (updated_at, id);



ALTER TABLE productos

ADD INDEX  idx_wm_productos (updated_at, id);



ALTER TABLE ventas

ADD INDEX  idx_wm_ventas (updated_at, id);



ALTER TABLE ventas_detalles

ADD INDEX  idx_wm_ventas_detalles (updated_at, id);



ALTER TABLE inventarios

ADD INDEX  idx_wm_inventarios (updated_at, id);



ALTER TABLE kardexes

ADD INDEX  idx_wm_kardexes (updated_at, id);



ALTER TABLE kardexes2

ADD INDEX  idx_wm_kardexes2 (updated_at, id);



ALTER TABLE cajas

ADD INDEX  idx_wm_cajas (updated_at, id);



---Nuevos



ALTER TABLE compras

ADD INDEX  idx_wm_compras (updated_at, id);



ALTER TABLE solicitudes

ADD INDEX  idx_wm_solicitudes (updated_at, id);



ALTER TABLE metas_users

ADD INDEX  idx_wm_metas_users (updated_at, id);



ALTER TABLE ajustes

ADD INDEX  idx_wm_ajustes (updated_at, id);



ALTER TABLE solicitudes_detalles

ADD INDEX  idx_wm_solicitudes_detalles (updated_at, id);



ALTER TABLE compras_detalles

ADD INDEX   idx_wm_compras_detalles(updated_at, id);



ALTER TABLE ajustes_detalles

ADD INDEX   idx_wm_ajustes_detalles(updated_at, id);



ALTER TABLE sync_states MODIFY watermark_updated_at DATETIME NULL;



ALTER TABLE clientes

ADD COLUMN id_vps BIGINT UNSIGNED NULL,

ADD INDEX idx_clientes_id_vps (id_vps);



UPDATE dtes           SET id_vps = id WHERE id_vps IS NULL;

UPDATE clientes       SET id_vps = id WHERE id_vps IS NULL;

UPDATE resumen_dtes   SET id_vps = id WHERE id_vps IS NULL;

UPDATE firmadors     SET id_vps = id WHERE id_vps IS NULL;

UPDATE recepcion_dtes SET id_vps = id WHERE id_vps IS NULL;

UPDATE compras          SET id_vps = id WHERE id_vps IS NULL;

UPDATE compras_detalles SET id_vps = id WHERE id_vps IS NULL;

UPDATE solicitudes          SET id_vps = id WHERE id_vps IS NULL;

UPDATE solicitudes_detalles SET id_vps = id WHERE id_vps IS NULL;

UPDATE metas_users        SET id_vps = id WHERE id_vps IS NULL;

UPDATE ajustes            SET id_vps = id WHERE id_vps IS NULL;

UPDATE ajustes_detalles   SET id_vps = id WHERE id_vps IS NULL;







-- Índices de incremental (si usas updated_at; cambia si tu TS es otro)

ALTER TABLE dtes

ADD INDEX idx_wm_dte (updated_at, id);



ALTER TABLE resumen_dtes

ADD INDEX idx_wm_resumen_dte (updated_at, id);



ALTER TABLE firmadors

ADD INDEX  idx_wm_firmadors (updated_at, id);



ALTER TABLE recepcion_dtes

ADD INDEX  idx_wm_recepcion_dte (updated_at, id);



-- (Recomendado) unicidad por sincro_id

ALTER TABLE dtes

ADD UNIQUE KEY uniq_sincro_dte (sincro_id);



ALTER TABLE resumen_dtes

ADD UNIQUE KEY uniq_sincro_resumen_dte (sincro_id);



ALTER TABLE firmadors

ADD UNIQUE KEY uniq_sincro_firmadors (sincro_id);



ALTER TABLE recepcion_dtes

ADD UNIQUE KEY uniq_sincro_recepcion_dte (sincro_id);





ALTER TABLE tockens

    ADD COLUMN  id_vps BIGINT UNSIGNED NULL,

    ADD INDEX  idx_tockens_id_vps (id_vps);



-- (opcional, si tu LOCAL viene de dump del VPS)

UPDATE tockens SET id_vps = id WHERE id_vps IS NULL;



-- índice para incremental (si usas updated_at)

ALTER TABLE tockens ADD INDEX  idx_wm_tockens (updated_at, id);



-- recomendado: asegurar unicidad por sincro_id

ALTER TABLE tockens

ADD UNIQUE KEY uniq_sincro_tockens (sincro_id);





ALTER TABLE cuentas_pagars

    ADD COLUMN  id_vps BIGINT UNSIGNED NULL,

    ADD INDEX  idx_cuentas_pagars_id_vps (id_vps);



-- (opcional, si tu LOCAL viene de dump del VPS)

UPDATE cuentas_pagars SET id_vps = id WHERE id_vps IS NULL;



-- índice para incremental (si usas updated_at)

ALTER TABLE cuentas_pagars ADD INDEX  idx_wm_cuentas_pagars (updated_at, id);



-- recomendado: asegurar unicidad por sincro_id

ALTER TABLE cuentas_pagars

ADD UNIQUE KEY uniq_sincro_cuentas_pagars (sincro_id);



ALTER TABLE pagos

    ADD COLUMN  id_vps BIGINT UNSIGNED NULL,

    ADD INDEX  idx_pagos_id_vps (id_vps);



-- (opcional, si tu LOCAL viene de dump del VPS)

UPDATE pagos SET id_vps = id WHERE id_vps IS NULL;



-- índice para incremental (si usas updated_at)

ALTER TABLE pagos ADD INDEX  idx_wm_pagos (updated_at, id);



-- recomendado: asegurar unicidad por sincro_id

ALTER TABLE pagos

ADD UNIQUE KEY uniq_sincro_pagos (sincro_id);





ALTER TABLE bitacora_sincronizacions

    ADD INDEX idx_bitacora_created_at (created_at);

-- (si prefieres purgar por 'fecha', crea también):

ALTER TABLE bitacora_sincronizacions

    ADD INDEX  idx_bitacora_fecha (fecha);


    ALTER TABLE proveedores
    ADD COLUMN  id_vps BIGINT UNSIGNED NULL,
    ADD INDEX  idx_proveedores_id_vps (id_vps);
		
		
		-- (opcional, si tu LOCAL viene de dump del VPS)
UPDATE proveedores SET id_vps = id WHERE id_vps IS NULL;

-- índice para incremental (si usas updated_at)
ALTER TABLE proveedores ADD INDEX  idx_wm_proveedores (updated_at, id);

-- recomendado: asegurar unicidad por sincro_id
ALTER TABLE proveedores
ADD UNIQUE KEY uniq_sincro_proveedores (sincro_id);


/*

php artisan storage:link

*/



/*

schtasks /Create /TN "Laravel Scheduler SuperTiendaRobert 5min" ^

 /TR "\"C:\laragon\www\supertiendarobert\run-scheduler.bat\"" ^

 /SC MINUTE /MO 5 /RU "SYSTEM" /RL HIGHEST /F



schtasks /Create /TN "Laravel Scheduler SuperTiendaRobert" ^

 /TR "\"C:\laragon\www\supertiendarobert\run-scheduler.bat\"" ^

 /SC MINUTE /MO 1 /RU "SYSTEM" /RL HIGHEST /F



php artisan sync:vps-local --stage=acl --acl-mode=snapshot

php artisan optimize:clear







*/

