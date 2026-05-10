from pathlib import Path

from app.recommender import RecommenderConfig, TourHubRecommender, weather_group


def test_weather_group():
    assert weather_group("Hujan Ringan") == "hujan"
    assert weather_group("Cerah Berawan") == "cerah"


def test_recommendation_returns_rows():
    recommender = TourHubRecommender(RecommenderConfig(data_path=Path("data/bali_tourist_destination.csv")))
    result, meta = recommender.recommend(
        kategori_preferensi=["Alam"],
        kabupaten_kota="Kabupaten Gianyar",
        weather="hujan",
        visit_day="weekend",
        top_n=5,
    )
    assert len(result) > 0
    assert "final_score" in result.columns
    assert meta["weather_group"] == "hujan"
