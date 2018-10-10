
INSERT INTO venues_bof ("name", capacity, lat, lng, city, country, state, zip, created_at, updated_at)
SELECT venue, venue_capacity, venue_lat, venue_lng, venue_city, venue_country, venue_state, venue_zip, now(), now()
FROM listings
GROUP BY venue, venue_city, venue_state, venue_country, venue_lat, venue_lng, venue_zip, venue_capacity;

UPDATE venues_bof 
SET venue_id = ven.id
FROM venues ven
WHERE venues_bof.name ILIKE ven.name
AND venues_bof.city ILIKE ven.city
AND venues_bof.state ILIKE ven.state_code;

SELECT ven_bof.name, ven.*
FROM venues_bof ven_bof
JOIN venues ven
ON ven_bof.name ILIKE ven.name
AND ven_bof.city ILIKE ven.city
AND ven_bof.state ILIKE ven.state_code;

UPDATE venues_bof 
SET capacity = NULL
WHERE capacity = 0;