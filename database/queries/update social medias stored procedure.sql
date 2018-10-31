CREATE OR REPLACE FUNCTION update_social_medias() 
    RETURNS void AS $$
    BEGIN
    
    update social_medias
    set seatgeek_id = sm_ul.seatgeek_id,
        seatgeek_name = sm_ul.seatgeek_name,
        seatgeek_score = sm_ul.seatgeek_score,
        spotify_name = sm_ul.spotify_name,
        spotify_id = sm_ul.spotify_id,
        spotify_followers = sm_ul.spotify_followers,
        spotify_popularity = sm_ul.spotify_popularity,
        lastfm_name = sm_ul.lastfm_name,
        lastfm_listeners = sm_ul.lastfm_listeners,
        nextbigsound_id = sm_ul.nextbigsound_id,
        nextbigsound_name = sm_ul.nextbigsound_name,
        nextbigsound_listeners = sm_ul.nextbigsound_listeners,
        nextbigsound_streams = sm_ul.nextbigsound_streams,
        nextbigsound_stage = sm_ul.nextbigsound_stage,
        nexbigsound_engagement = sm_ul.nexbigsound_engagement,
        nextbigsound_facebook_likes = sm_ul.nextbigsound_facebook_likes,
        nextbigsound_twitter_followers = sm_ul.nextbigsound_twitter_followers,
        nextbigsound_songkick_followers = sm_ul.nextbigsound_songkick_followers
    from social_medias_upload sm_ul
    where social_medias.attraction_id = sm_ul.attraction_id;
    
    END;
    $$ LANGUAGE plpgsql;