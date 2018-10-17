
--/users/sungwhikim/downloads/venues_master_list.csv            
COPY venues_temp("name", venue_id, venue_capacity, venue_lat, venue_lng, venue_city, venue_country, venue_state, venue_zip) 
FROM '/users/sungwhikim/downloads/venues_master_list.csv' DELIMITER ';' CSV HEADER;           

SELECT * FROM venues_temp; 

SELECT count(*)
FROM venues_temp
RIGHT JOIN venues_bof
    ON venues_temp.name = venues_bof.name
GROUP BY venues_temp.name, venues_temp.venue_city, venues_temp.venue_state    
    
SELECT * FROM venues_bof;  
  
SELECT count(*) FROM venues_bof    
GROUP BY "name", city, state;

SELECT * INTO venues_bof_old
FROM venues_temp

DROP TABLE venues_bof_old

SELECT * FROM venues_bof_old

INSERT INTO venues_bof("name", venue_id, capacity, lat, lng, city, country, state, zip, created_at, updated_at)
SELECT venues_temp.name, 
        venues_temp.venue_id,
        venues_temp.venue_capacity,
        venues_temp.venue_lat,
        venues_temp.venue_lng,
        venues_temp.venue_city, 
        venues_temp.venue_country,
        venues_temp.venue_state,   
        venues_temp.venue_zip, 
        now(), 
        now() 
FROM venues_temp
GROUP BY 
    venues_temp.venue_id,
    venues_temp.name, 
    venues_temp.venue_city, 
    venues_temp.venue_state,   
    venues_temp.venue_country,
    venues_temp.venue_capacity,
    venues_temp.venue_lat,
    venues_temp.venue_lng,
    venues_temp.venue_zip
    
UPDATE venues_bof
SET capacity = NULL
WHERE capacity = 0;
    
UPDATE venues
SET capacity = venues_bof.capacity
FROM venues_bof
WHERE venues.id = venues_bof.venue_id

UPDATE venues
SET capacity = NULL
WHERE capacity = 0;

SELECT count(*) FROM venues WHERE capacity IS NOT NULL;




