CREATE VIEW events_view AS

SELECT  evt.id,
        evt.tm_id,
        evt.name,
        evt.type,
        evt.url,
        evt.locale,
        evt.currency,
        evt.public_sale_datetime,
        evt.sales_start_tbd,
        evt.event_local_date,
        evt.event_local_time,
        evt.event_time_zone,
        evt.event_datetime,
        evt.event_status_code,
        evt.segment_id,
        seg.name AS segment_name,
        evt.genre_id,
        gen.name AS genre_name,
        evt.sub_genre_id,
        sub_gen.name AS sub_genre_name,
        evt.ticket_limit,
        evt.ticket_max_number,
        evt.data_master_id,
        evt.created_at,
        evt.updated_at
FROM events evt 
    LEFT JOIN segments seg
        ON evt.segment_Id = seg.id
    LEFT JOIN genres gen
        ON evt.genre_id = gen.id
    LEFT JOIN sub_genres sub_gen
        ON evt.sub_genre_id = sub_gen.id