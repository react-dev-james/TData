SELECT  --min(evt.id) as event_id,
        --evt.id,
        min(evt.event_datetime),
        min(evt.id),
        evt.name,
        att.id,
        --att.name as attraction_name,
        ven.id,
        ven.name AS venue_name
        --min(evt.event_datetime)
FROM events evt
    LEFT JOIN event_attraction evt_att
        ON evt.id = evt_att.event_id
    LEFT JOIN attractions att
        ON evt_att.attraction_id = att.id
    LEFT JOIN event_venue evt_ven
        ON evt.id = evt_ven.event_id
    LEFT JOIN venues ven
        ON evt_ven.venue_id = ven.id    
WHERE evt_att.primary = TRUE    
AND evt_ven.primary = TRUE   
AND evt.name = 'A Bronx Tale: The Musical (Touring)'
GROUP BY att.id, ven.id, ven.name, evt.name
ORDER BY evt.name