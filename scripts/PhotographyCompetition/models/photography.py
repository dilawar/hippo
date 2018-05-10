from credentials.server_paths import BASE_URL
from user_constants import default_value


class PhotoObject:
    """
    Simple class to hold all information about single photo
    """

    def __init__(self):
        # Relative url. Need this to retrieve email ID or more specific
        # details. BEAWARE! this can be deleted for old photos
        self.url = None
        self.author = None  # Name of photographer as shown in the intranet
        # website
        self.title = None  # Photo caption
        self.total_votes = None  # Total number of votes (if any)
        self.average_votes = None  # Average votes (if any)
        self.creation_date = None  # Date of photo upload
        self.points = 0  # Final points from voting
        self.photo_url = None  # Absolute path of photo. BEAWARE! this can
        # be deleted for old photos
        self.author_email = None  # This will not be exact email, because
        # intranet userID's are weirdly formatted
        self.file_name = None  # Name of file in which we have saved photo
        # on local machine
        self.key = None  # Unique identifier. Will be helpful in analysis
        self.description = "N/A"

    @classmethod
    def make(cls, properties: dict):
        """
        Makes class instance from dictionary object
        :param properties: dict received from json
        :return: PhotoObject instance
        """
        photo = cls()
        photo.author = properties.get("author")
        photo.url = properties.get("url")
        photo.title = properties.get("title")
        photo.photo_url = properties.get("photo_url")
        photo.author_email = properties.get("author_email")
        photo.file_name = properties.get("file_name")
        photo.key = properties.get("key")
        photo.total_votes = properties.get("total_votes")
        photo.creation_date = properties.get("creation_date")
        photo.average_votes = properties.get("average_votes")
        photo.description = properties.get("description")

        if photo.creation_date == default_value:
            photo.creation_date = None
        if photo.total_votes == default_value:
            photo.total_votes = None
        if photo.average_votes == default_value:
            photo.average_votes = None
        return photo

    def get_all(self) -> list:
        """
        Returns all associated information after formatting.
        This function can be directly used in printing into file
        :return: list of all the meta-data
        """
        return [self.author,
                self.author_email,
                self.points,
                self.file_name,
                self.title,
                self.description,
                BASE_URL + self.url,
                self.total_votes,
                self.average_votes,
                self.creation_date,
                self.photo_url]


class Judge:
    def __init__(self):
        pass
