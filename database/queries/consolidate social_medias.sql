SELECT * FROM social_medias;

SELECT count(DISTINCT(attraction_id)) FROM social_medias;

SELECT count(id)
    FROM social_medias
    GROUP BY attraction_id
    HAVING count(*) > 1
    
SELECT * INTO social_medias_backup FROM social_medias;    

DELETE FROM social_medias
WHERE id IN(
    SELECT max(id)
    FROM social_medias
    GROUP BY attraction_id
    HAVING count(*) > 1
)

DROP COLUMN social_media_types FROM social_medias;





