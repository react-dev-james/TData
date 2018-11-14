DROP VIEW listings_view;

CREATE VIEW listings_view AS

SELECT  evt.id AS event_id,
        evt.tm_id,
        evt.name AS event_name,
        att.id AS attraction_id,
        att.name AS attraction_name,
        att.upcoming_events,
        ven.id AS venue_id,
        ven.name AS venue_name,
        ven.city AS venue_city,
        ven.state_code AS venue_state_code,
        ven.capacity AS venue_capacity,
        evt.type,
        evt.url AS ticket_url,
        evt.listing_id,
        evt.offer_code AS offer_code,
        evt.locale,
        evt.public_sale_datetime,
        min(evt_psl.start_datetime) AS presale_datetime,
        CASE
            WHEN evt.public_sale_datetime < min(evt_psl.start_datetime) OR min(evt_psl.start_datetime) IS NULL
            THEN
                evt.public_sale_datetime
            ELSE
                min(evt_psl.start_datetime)
        END AS first_onsale_datetime,   
        CASE
            WHEN evt.public_sale_datetime < min(evt_psl.start_datetime) OR min(evt_psl.start_datetime) IS NULL
            THEN
                EXTRACT(epoch FROM evt.public_sale_datetime)
            ELSE
                EXTRACT(epoch FROM min(evt_psl.start_datetime))
        END AS first_onsale_timestamp,      
        --evt_psl.name AS presale_name,
        evt.sales_start_tbd,
        evt.event_local_date,
        evt.event_local_time,
        evt.event_time_zone,
        evt.event_datetime,
        to_char(evt.event_datetime, 'day') AS event_day,
        evt.event_status_code,
        evt.event_state_id,
        evt_st.title AS event_state,
        evt_st.slug AS event_state_slug,
        seg.name AS segment_name,
        gen.name AS genre_name,
        sub_gen.name AS sub_genre_name,
        evt.ticket_limit,
        evt.ticket_max_number,
        evt.created_at AS event_created_at,
        evt.updated_at AS event_updated_at,
        evt.currency,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                evt.price_range_min
            WHEN evt.currency ILIKE 'CAD'
            THEN
                round(evt.price_range_min / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
        END AS price_range_min,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                evt.price_range_max
            WHEN evt.currency ILIKE 'CAD'
            THEN
                round(evt.price_range_max / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
        END AS price_range_max,
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil((
(dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')))  
/ 
((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))  / ((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100))  / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                 END        
            ELSE
                0
        END AS price_range_roi_low,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil((
(dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')))  
/ 
((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))  / ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100))  / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                 END        
            ELSE
                0
        END AS price_range_roi_high,  
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                min(evt_prc.total)
            WHEN evt.currency ILIKE 'CAD'
            THEN
                round(min(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
        END AS min_price_offer,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                array_min
                (
                    ARRAY ( 
                           SELECT total FROM event_prices 
                           WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )
                ) 
            WHEN evt.currency ILIKE 'CAD'
            THEN  
                round(
                    array_min
                    (
                        ARRAY ( 
                               SELECT total FROM event_prices 
                               WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )
                    ) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')
                 )
        END AS second_highest_price_offer,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                max(evt_prc.total)
            WHEN evt.currency ILIKE 'CAD'
            THEN
                round(max(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
        END AS max_price_offer,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                round(avg(evt_prc.total))
            WHEN evt.currency ILIKE 'CAD'
            THEN
                round(avg(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
        END AS average_price_offer,
        CASE
            WHEN dm.weighted_avg IS NOT NULL AND min(evt_prc.total) > 0
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  min(evt_prc.total))  / min(evt_prc.total)) * 100)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  min(evt_prc.total))  / min(evt_prc.total)) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                 END        
            ELSE
                NULL
        END AS roi_low_offer,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL AND max(evt_prc.total) > 0
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) - array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )))  / array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 ))) * 100)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) - array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )))  / array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 ))) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                END    
            ELSE
                NULL
        END AS roi_second_highest_offer,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL AND max(evt_prc.total) > 0
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total)) * 100)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total)) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                END    
            ELSE
                NULL
        END AS roi_high_offer,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL AND max(evt_prc.total) > 0
            THEN
                CASE
                    WHEN evt.currency ILIKE 'USD'
                    THEN
                        ceil(((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total)) * 40)
                    WHEN evt.currency ILIKE 'CAD'
                    THEN
                        round(ceil(((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total) * 40) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                END    
            ELSE
                NULL
        END AS roi_net_offer,  
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                CASE 
                    WHEN min(evt_prc.total) IS NOT NULL
                    THEN
                        min(evt_prc.total)
                    ELSE   
                        evt.price_range_min
                END        
            WHEN evt.currency ILIKE 'CAD'
            THEN
                CASE 
                    WHEN min(evt_prc.total) IS NOT NULL
                    THEN
                        round(min(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                    ELSE   
                        round(evt.price_range_min / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')) 
                END      
        END AS min_price,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                CASE 
                    WHEN max(evt_prc.total) IS NOT NULL
                    THEN
                        array_min
                        (
                            ARRAY ( 
                                   SELECT total FROM event_prices 
                                   WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )
                        ) 
                     ELSE
                         evt.price_range_max
                END        
            WHEN evt.currency ILIKE 'CAD'
            THEN  
                CASE
                    WHEN max(evt_prc.total) IS NOT NULL
                    THEN
                        round(
                            array_min
                            (
                                ARRAY ( 
                                       SELECT total FROM event_prices 
                                       WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )
                            ) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')
                         )
                     ELSE   
                        round(evt.price_range_max / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')) 
                END     
        END AS second_highest_price,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                CASE 
                    WHEN max(evt_prc.total) IS NOT NULL
                    THEN
                        max(evt_prc.total)
                    ELSE   
                        evt.price_range_max
                END        
            WHEN evt.currency ILIKE 'CAD'
            THEN
                CASE 
                    WHEN max(evt_prc.total) IS NOT NULL
                    THEN
                        round(max(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                    ELSE   
                        round(evt.price_range_max / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')) 
                END      
        END AS max_price,
        CASE
            WHEN evt.currency ILIKE 'USD'
            THEN
                CASE 
                    WHEN min(evt_prc.total) IS NOT NULL
                    THEN
                        round(avg(evt_prc.total))
                    ELSE   
                        round((price_range_min + price_range_max) / 2)
                END        
            WHEN evt.currency ILIKE 'CAD'
            THEN
                CASE 
                    WHEN min(evt_prc.total) IS NOT NULL
                    THEN
                        round(avg(evt_prc.total) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                    ELSE   
                        round(((evt.price_range_min + evt.price_range_max) / 2) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion')) 
                END      
        END AS average_price,
        CASE
            WHEN dm.weighted_avg IS NOT NULL 
            THEN
                CASE 
                    WHEN min(evt_prc.total) > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  min(evt_prc.total))  / min(evt_prc.total)) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  min(evt_prc.total))  / min(evt_prc.total)) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                         END  
                     WHEN evt.price_range_min > 0
                     THEN
                         CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((
        (dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
        ((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')))  
        / 
        ((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))  / ((evt.price_range_min * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100))  / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                         END        
                     ELSE
                         NULL   
                END               
            ELSE
                NULL
        END AS roi_low,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
                CASE
                    WHEN max(evt_prc.total) > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) - array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )))  / array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 ))) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) - array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 )))  / array_min(ARRAY (SELECT total FROM event_prices WHERE event_id = evt.id ORDER BY total DESC LIMIT 2 ))) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                        END    
                    WHEN evt.price_range_max > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((
        (dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
        ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')))  
        / 
        ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))  / ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100))  / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                         END        
                    ELSE
                         NULL
                END         
            ELSE
                NULL
        END AS roi_second_highest,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
                CASE
                    WHEN max(evt_prc.total) > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total)) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total)) * 100) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                        END  
                     WHEN evt.price_range_max > 0
                     THEN
                         CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                ceil((
        (dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
        ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')))  
        / 
        ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))  / ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition')) * 100))  / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                         END       
                     ELSE
                         NULL
                END    
            ELSE
                NULL
        END AS roi_high,  
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
                CASE 
                    WHEN max(evt_prc.total) > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                round(((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total)) * 40)
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(ceil(((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  max(evt_prc.total))  / max(evt_prc.total) * 40) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))
                        END    
                    WHEN evt.price_range_max > 0
                    THEN
                        CASE
                            WHEN evt.currency ILIKE 'USD'
                            THEN
                                round(
        ((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  
        ((evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))) * 40)  
                            WHEN evt.currency ILIKE 'CAD'
                            THEN
                                round(((((dm.weighted_avg * .94 * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date))) -  (evt.price_range_max * (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_multiplier')) + (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'ROI_price_addition'))) * 40) / (SELECT "value" FROM ticket_master_view_settings WHERE "key" = 'CAD_conversion'))  
                         END       
                    ELSE
                        NULL
                END        
            ELSE
                NULL
        END AS roi_net,  
        evt.data_master_id,
        dm.total_events,
        CASE
            WHEN dm.weighted_avg IS NOT NULL
            THEN
              ceil(dm.weighted_avg * (SELECT adjustment FROM weekday_adjustment WHERE weekday = EXTRACT(dow FROM evt.event_local_date)))             
        ELSE
            0
        END AS weighted_sold,  
        CASE 
            WHEN dm.tn_vol IS NOT NULL 
                 AND dm.tn_tix_sold IS NOT NULL 
                 AND dm.total_sold IS NOT NULL 
                 AND dm.total_vol IS NOT NULL 
                 AND (dm.total_vol - dm.tn_vol) > 0 
                 AND (dm.total_sold - dm.tn_tix_sold) > 0
            THEN
               round((dm.total_vol - dm.tn_vol) / (dm.total_sold - dm.tn_tix_sold))
        ELSE
           NULL
        END AS sh_sold,   
        dm.total_sold,
        dm.total_vol,
        dm.weighted_avg,
        dm.tot_per_event,
        dm.td_events,
        dm.td_tix_sold,
        dm.td_vol,
        dm.tn_events,
        dm.tn_tix_sold,
        dm.tn_vol,
        dm.tn_avg_sale,
        dm.levi_events,
        dm.levi_tix_sold,
        dm.levi_vol,
        dm.si_events,
        dm.si_tix_sold,
        dm.si_vol,
        dm.sfc_roi,
        dm.sfc_roi_dollar,
        dm.sfc_cogs,
        soc_med.spotify_popularity,
        soc_med.spotify_followers,
        soc_med.nextbigsound_listeners,
        soc_med.nextbigsound_streams,
        soc_med.nextbigsound_facebook_likes,
        soc_med.nextbigsound_twitter_followers,
        soc_med.nextbigsound_songkick_followers,
        soc_med.lastfm_listeners 
FROM events evt
    LEFT JOIN event_states evt_st
        ON evt.event_state_id = evt_st.id
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
    LEFT JOIN social_medias soc_med
        ON att.id = soc_med.attraction_id
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
    JOIN (
            SELECT  evt.name AS event_name,
                    min(evt.event_datetime) AS event_datetime,
                    att.id AS attraction_id,
                    ven.id AS venue_id
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
            GROUP BY att.id, ven.id, ven.name, evt.name
        ) first_event_date 
        ON first_event_date.event_name = evt."name"
            AND first_event_date.event_datetime = evt.event_datetime
            AND first_event_date.attraction_id = att.id
            AND first_event_date.venue_id = ven.id
WHERE evt_att.primary = TRUE    
AND evt_ven.primary = TRUE    
GROUP BY
        evt.id,
        evt.tm_id,
        evt.event_state_id,
        evt_st.title,
        evt_st.slug,
        evt.name,
        ven.id,
        ven.name,
        ven.state_code,
        ven.capacity,
        att.id,
        att.name,
        att.upcoming_events,
        evt.type,
        evt.url,
        evt.listing_id,
        evt.offer_code,
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
        evt.created_at,
        evt.updated_at,
        --evt_psl.name,
        evt.data_master_id, 
        dm.total_events,
        dm.total_sold,
        dm.total_vol,
        dm.weighted_avg,
        dm.tot_per_event,
        dm.td_events,
        dm.td_tix_sold,
        dm.td_vol,
        dm.tn_events,
        dm.tn_tix_sold,
        dm.tn_vol,
        dm.tn_avg_sale,
        dm.levi_events,
        dm.levi_tix_sold,
        dm.levi_vol,
        dm.si_events,
        dm.si_tix_sold,
        dm.si_vol,
        dm.sfc_roi,
        dm.sfc_roi_dollar,
        dm.sfc_cogs,
        soc_med.spotify_popularity,
        soc_med.spotify_followers,
        soc_med.nextbigsound_listeners,
        soc_med.nextbigsound_streams,
        soc_med.nextbigsound_facebook_likes,
        soc_med.nextbigsound_twitter_followers,
        soc_med.nextbigsound_songkick_followers,
        soc_med.lastfm_listeners;
        
GRANT SELECT ON listings_view TO tickets;
   
/*   
    create or replace function array_min(anyarray) returns anyelement
as
$$
select min(unnested) from( select unnest($1) unnested ) as x
$$ language sql;
*/