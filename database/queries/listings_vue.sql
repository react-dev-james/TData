--CREATE VIEW listings_view AS


SELECT  evt.tm_id,
        evt."fromBoxOfficeFox",
        evt.name,
        att.name AS attraction_name,
        att.upcoming_events,
        ven.name AS venue_name,
        evt.type,
        evt.url,
        evt.locale,
        evt.public_sale_datetime,
        min(evt_psl.start_datetime) AS presale_datetime,
        evt_psl.name AS presale_name,
        evt.sales_start_tbd,
        evt.event_local_date,
        evt.event_local_time,
        evt.event_time_zone,
        evt.event_datetime,
        evt.event_status_code,
        seg.name AS segment_name,
        gen.name AS genre_name,
        sub_gen.name AS sub_genre_name,
        evt.ticket_limit,
        evt.ticket_max_number,
        evt.created_at AS event_created_at,
        evt.updated_at AS event_updated_at,
        evt.data_master_id,
        evt.currency,
        min(evt_prc.total) AS min_price,
        max(evt_prc.total) AS max_price,
        array_min
        (
            ARRAY ( 
                   SELECT total FROM event_prices 
                   WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )
        ) AS second_highest_price,
        round(avg(evt_prc.total)) AS average_price
FROM events evt
    LEFT JOIN segments seg
        ON evt.segment_Id = seg.id
    LEFT JOIN genres gen
        ON evt.genre_id = gen.id
    LEFT JOIN sub_genres sub_gen
        ON evt.sub_genre_id = sub_gen.id
    LEFT JOIN event_attraction evt_att
        ON evt.id = evt_att.event_id
    LEFT JOIN attractions att
        ON evt_att.attraction_id = att.id
    LEFT JOIN event_venue evt_ven
        ON evt.id = evt_ven.event_id
    LEFT JOIN venues ven
        ON evt_ven.venue_id = ven.id    
    LEFT JOIN event_prices evt_prc
        ON evt.id = evt_prc.event_id    
    LEFT JOIN event_presales evt_psl
        ON evt.id = evt_psl.event_id        
    LEFT JOIN data_master dm
        ON evt.data_master_id = dm.id    
WHERE evt_att.primary = TRUE    
AND evt_ven.primary = TRUE    
GROUP BY 
        evt.id,
        evt.tm_id,
        evt."fromBoxOfficeFox",
        evt.name,
        ven.name,
        att.name,
        att.upcoming_events,
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
        seg.name,
        gen.name,
        sub_gen.name,
        evt.ticket_limit,
        evt.ticket_max_number,
        evt.data_master_id,
        evt.created_at,
        evt.updated_at,
        evt_psl.name 
   
/*   
    create or replace function array_min(anyarray) returns anyelement
as
$$
select min(unnested) from( select unnest($1) unnested ) as x
$$ language sql;
*/