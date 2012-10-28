<?php

namespace Foolz\Foolfuuka\Model;

class MediaException extends \Exception {}
class MediaNotFoundException extends MediaException {}
class MediaHashNotFoundException extends MediaNotFoundException {}
class MediaDirNotAvailableException extends MediaNotFoundException {}
class MediaFileNotFoundException extends MediaNotFoundException {}

class MediaUploadException extends \Exception {}
class MediaUploadNoFileException extends MediaUploadException {}
class MediaUploadMultipleNotAllowedException extends MediaUploadException {}
class MediaUploadInvalidException extends MediaUploadException {}

class MediaInsertException extends \Exception {}
class MediaInsertInvalidFormatException extends MediaInsertException {}
class MediaInsertDomainException extends MediaInsertException {}
class MediaInsertRepostException extends MediaInsertException {}

/**
 * Manages Media files and database
 */
class Media extends \Model\Model_Base
{
	/**
	 * If the media is referred to an opening post
	 *
	 * @var  boolean
	 */
	public $op = false;

	/**
	 * The autoincremented value
	 *
	 * @var  int
	 */
	public $media_id = 0;

	/**
	 * If the media should be spoilered
	 *
	 * @var  boolean
	 */
	public $spoiler = false;

	/**
	 * The autogenerated filename for the preview
	 *
	 * @var  string
	 */
	public $preview_orig = null;

	/**
	 * The preview height
	 *
	 * @var  width
	 */
	public $preview_w = 0;

	/**
	 * The preview height
	 *
	 * @var  int
	 */
	public $preview_h = 0;

	/**
	 * The original filename
	 *
	 * @var  string
	 */
	public $media_filename = null;

	/**
	 * The width of the full media
	 *
	 * @var  int
	 */
	public $media_w = 0;

	/**
	 * The height of the full media
	 *
	 * @var  int
	 */
	public $media_h = 0;

	/**
	 * The media size in bytes
	 *
	 * @var  int
	 */
	public $media_size = 0;

	/**
	 * The MD5(-based algorithm) of the media
	 *
	 * @var  string
	 */
	public $media_hash = null;

	/**
	 * The autogenerated filename for the full media
	 *
	 * @var  string
	 */
	public $media_orig = null;

	/**
	 * The exif data for the media
	 *
	 * @var  string
	 */
	public $exif = null;

	/**
	 * The amount of reposts for the media
	 *
	 * @var  int
	 */
	public $total = 0;

	/**
	 * If the image is banned
	 *
	 * @var  int
	 */
	public $banned = false;

	/**
	 * The filename with which the full media is stored
	 *
	 * @var  string
	 */
	public $media = null;

	/**
	 * The filename with which the preview for the OP is stored
	 *
	 * @var  string
	 */
	public $preview_op = null;

	/**
	 * The filename with which the preview for the reply is stored
	 *
	 * @var  string
	 */
	public $preview_reply = null;

	/**
	 * The radix object for the media
	 *
	 * @var \Foolz\Foolfuuka\Model\Radix
	 */
	public $radix = null;

	/**
	 * The temporary path for the uploaded file
	 *
	 * @var  string
	 */
	public $temp_path = null;

	/**
	 * The temporary filename for the uploaded file
	 *
	 * @var  string
	 */
	public $temp_filename = null;

	/**
	 * The temporary extension for the uploaded file
	 *
	 * @var  string
	 */
	public $temp_extension = null;

	/**
	 * Caches media_status value
	 *
	 * @var  false|int  false if not yet cached
	 */
	public $media_status = false;

	/**
	 * Caches the media hash converted for URLs
	 *
	 * @var  false|string  false if not yet cached
	 */
	public $safe_media_hash = false;

	/**
	 * Caches the remote media link, an URL to external resources
	 *
	 * @var  false|string  false if not yet cached
	 */
	public $remote_media_link = false;

	/**
	 * Caches the media link, the direct URL to the resource
	 *
	 * @var  false|null|string  false if not cached, null if not found, string if found
	 */
	public $media_link = false;

	/**
	 * Caches the thumb link, the direct URL to the thumbnail
	 *
	 * @var  false|null|string  false if not cached, null if not found, string if found
	 */
	public $thumb_link = false;

	/**
	 * Caches the sanitized media filename
	 *
	 * @var  false|string  false if not cached
	 */
	public $media_filename_processed = false;

	/**
	 * The array of fields that are part of the database
	 *
	 * @var  array
	 */
	public static $fields = [
		'media_id',
		'spoiler',
		'preview_orig',
		'media',
		'preview_op',
		'preview_reply',
		'preview_w',
		'preview_h',
		'media_filename',
		'media_w',
		'media_h',
		'media_size',
		'media_hash',
		'media_orig',
		'exif',
		'total',
		'banned'
	];

	/**
	 * Status for media that has no flags on it
	 */
	const STATUS_NORMAL = 'normal';

	/**
	 * Status for media that was banned
	 */
	const STATUS_BANNED = 'banned';

	/**
	 * Status for media that the user can't access
	 */
	const STATUS_FORBIDDEN = 'forbidden';

	/**
	 * Status for media that isn't found on server
	 */
	const STATUS_NOT_AVAILABLE = 'not-available';

	/**
	 * Returns static::$fields
	 *
	 * @see static::fields
	 * @return type
	 */
	public static function getFields()
	{
		return static::$fields;
	}

	/**
	 * Takes the data from a Comment to forge a Media object
	 *
	 * @param  object|array                  $comment  An array or object, the construct will use its keys to create a Media object
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix    The Radix in which the Media can be found
	 * @param  boolean                       $op       If this media is referred to an opening post
	 */
	public function __construct($comment, \Foolz\Foolfuuka\Model\Radix $radix, $op = false)
	{
		$this->radix = $radix;

		foreach ($comment as $key => $item)
		{
			$this->$key = $item;
		}

		$this->op = $op;

		if ($this->radix->archive)
		{
			// archive entries for media_filename are already encoded and we risk overencoding
			$this->media_filename = html_entity_decode($this->media_filename, ENT_QUOTES, 'UTF-8');
		}

		// let's unset 0 sizes so maybe the __get() can save the day
		if ( ! $this->preview_w || ! $this->preview_h)
		{
			$this->preview_h = 0;
			$this->preview_w = 0;

			if ($this->radix->archive && $this->spoiler)
			{
				try
				{
					$imgsize = \Cache::get('fu.media.call.spoiler_size.'.$this->radix->id.'.'.$this->media_id.'.'.($this->op ? 'op':'reply'));
					$this->preview_w = $imgsize[0];
					$this->preview_h = $imgsize[1];
				}
				catch (\CacheNotFoundException $e)
				{
					$imgpath = $this->getDir(true);
					if ($imgpath !== null)
					{
						$imgsize = false;
						$imgsize = @getimagesize($imgpath);

						\Cache::set('fu.media.call.spoiler_size.'.$this->radix->id.'.'.$this->media_id.'.'.($this->op ? 'op':'reply'), $imgsize, 86400);

						if ($imgsize !== false)
						{
							$this->preview_w = $imgsize[0];
							$this->preview_h = $imgsize[1];
						}
					}
				}
			}
		}

		// we set them even for admins
		if ($this->banned)
		{
			$this->media_status = static::STATUS_BANNED;
		}
		else if ($this->radix->hide_thumbnails && ! \Auth::has_access('media.see_hidden'))
		{
			$this->media_status = static::STATUS_FORBIDDEN;
		}
		else
		{
			$this->media_status = static::STATUS_NORMAL;
		}
	}

	/**
	 * Checks if there's a file stored in the cache and gets rid of it
	 */
	public function __destruct()
	{
		if ($this->temp_filename !== null && file_exists($this->temp_path.$this->temp_filename))
		{
			unlink($this->temp_path.$this->temp_filename);
		}
	}

	/**
	 * Creates an empty Media object
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix the Media will refer to
	 *
	 * @return \Foolz\Foolfuuka\Model\Media  An empty Media object, with all the values unset
	 */
	public static function forgeEmpty(\Foolz\Foolfuuka\Model\Radix $radix)
	{
		$media = new \stdClass();
		return new Media($media, $radix);
	}

	/**
	 * Return a Media object by a chosen column
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix where the Media can be found
	 * @param  string                        $where  The database column to match on
	 * @param  string                        $value  The value searched for
	 * @param  boolean                       $op     If the object is for an opening post
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media  The searched object
	 * @throws  MediaNotFoundException        If the media has not been found
	 */
	protected static function p_getBy(\Foolz\Foolfuuka\Model\Radix $radix, $where, $value, $op = true)
	{
		$result = \DC::qb()
			->select('*')
			->from($radix->getTable('_images'), 'ri')
			->where(\DC::forge()->quoteIdentifier($where).' = '.\DC::forge()->quote($value))
			->execute()
			->fetch();

		if ($result)
		{
			return new Media($result, $radix, $op);
		}

		throw new MediaNotFoundException(__('The image could not be found.'));
	}

	/**
	 * Return a Media object by the media_id column
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix where the Media can be found
	 * @param  string                        $value  The media ID
	 * @param  boolean                       $op     If the object is for an opening post
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media  The searched object
	 * @throws  MediaNotFoundException        If the media has not been found
	 */
	protected static function p_getByMediaId(\Foolz\Foolfuuka\Model\Radix $radix, $value, $op = false)
	{
		return static::getBy($radix, 'media_id', $value, $op);
	}

	/**
	 * Return a Media object by the media_hash column
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix where the Media can be found
	 * @param  string                        $value  The media hash
	 * @param  boolean                       $op     If the object is for an opening post
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media  The searched object
	 * @throws  MediaNotFoundException        If the media has not been found
	 */
	protected static function p_getByMediaHash(\Foolz\Foolfuuka\Model\Radix $radix, $value, $op = false)
	{
		return static::getBy($radix, 'media_hash', $value, $op);
	}

	/**
	 * Return a Media object by the media_hash column
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix where the Media can be found
	 * @param  string                        $value  The filename
	 * @param  boolean                       $op     If the object is for an opening post
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media  The searched object
	 * @throws  MediaNotFoundException        If the media has not been found
	 */
	protected static function p_getByFilename(\Foolz\Foolfuuka\Model\Radix $radix, $filename, $op = false)
	{
		$result = \DC::qb()
			->select('media_id')
			->from($radix->getTable(). 'r')
			->where('r.media_orig = :media_orig')
			->setParameter(':media_orig', $filename)
			->execute()
			->fetch();

		if ($result)
		{
			return static::getByMediaId($radix, $result->media_id, $op);
		}

		throw new MediaNotFoundException;
	}

	/**
	 * Takes an uploaded file and makes an object. It doesn't do the ->insert()
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix where this Media belongs
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media            A new Media object with the upload data
	 * @throws  MediaUploadNoFileException              If there's no file uploaded
	 * @throws  MediaUploadMultipleNotAllowedException  If there's multiple uploads
	 * @throws  MediaUploadInvalidException             If the file format is not allowed
	 */
	protected static function p_forgeFromUpload(\Foolz\Foolfuuka\Model\Radix $radix)
	{
		\Upload::process([
			'path' => APPPATH.'tmp/media_upload/',
			'max_size' => \Auth::has_access('media.limitless_media') ? 9999 * 1024 * 1024 : $radix->max_image_size_kilobytes * 1024,
			'randomize' => true,
			'max_length' => 64,
			'ext_whitelist' => ['jpg', 'jpeg', 'gif', 'png'],
			'mime_whitelist' => ['image/jpeg', 'image/png', 'image/gif']
		]);

		if (count(\Upload::get_files()) === 0)
		{
			throw new MediaUploadNoFileException(__('You must upload an image or your image was too large.'));
		}

		if (count(\Upload::get_files()) !== 1)
		{
			throw new MediaUploadMultipleNotAllowedException(__('You can\'t upload multiple images.'));
		}

		if (\Upload::is_valid())
		{
			// save them according to the config
			\Upload::save();
		}

		$file = \Upload::get_files(0);

		if ( ! \Upload::is_valid())
		{
			if (in_array($file['errors'], UPLOAD_ERR_INI_SIZE))
			{
				throw new MediaUploadInvalidException(
					__('The server is misconfigured: the FoolFuuka upload size should be lower than PHP\'s upload limit.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_PARTIAL))
			{
				throw new MediaUploadInvalidException(__('You uploaded the file partially.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_CANT_WRITE))
			{
				throw new MediaUploadInvalidException(__('The image couldn\'t be saved on the disk.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_EXTENSION))
			{
				throw new MediaUploadInvalidException(__('A PHP extension broke and made processing the image impossible.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_MAX_SIZE))
			{
				throw new MediaUploadInvalidException(
					\Str::tr(__('You uploaded a too big file. The maxmimum allowed filesize is :sizekb'),
						['size' => $radix->max_image_size_kilobytes]));
			}

			if (in_array($file['errors'], UPLOAD_ERR_EXT_NOT_WHITELISTED))
			{
				throw new MediaUploadInvalidException(__('You uploaded a file with an invalid extension.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_MAX_FILENAME_LENGTH))
			{
				throw new MediaUploadInvalidException(__('You uploaded a file with a too long filename.'));
			}

			if (in_array($file['errors'], UPLOAD_ERR_MOVE_FAILED))
			{
				throw new MediaUploadInvalidException(__('Your uploaded file couldn\'t me moved on the server.'));
			}

			throw new MediaUploadInvalidException(__('Unexpected upload error.'));
		}

		$media = [
			'media_filename' => $file['name'],
			'media_size' => $file['size'],
			'temp_path' => $file['saved_to'],
			'temp_filename' => $file['saved_as'],
			'temp_extension' => $file['extension']
		];

		return new Media($media, $radix);
	}

	/**
	 * Returns the media_status and caches the result
	 *
	 * @return  string
	 */
	public function getMediaStatus()
	{
		if ($this->media_status === false)
		{
			$this->media_link = $this->getLink(false);
		}

		return $this->media_status;
	}

	/**
	 * Returns the safe media hash and caches the result
	 *
	 * @return  string
	 */
	public function getSafeMediaHash()
	{
		if ($this->safe_media_hash === false)
		{
			$this->safe_media_hash = $this->getHash(true);
		}

		return $this->safe_media_hash;
	}

	/**
	 * Returns the remote media link and caches the result
	 *
	 * @see getRemoteLink()
	 *
	 * @return  string|null
	 */
	public function getRemoteMediaLink()
	{
		if ($this->remote_media_link === false)
		{
			$this->remote_media_link = $this->getRemoteLink();
		}

		return $this->remote_media_link;
	}

	/**
	 * Returns the media_link and caches the result
	 *
	 * @see getLink(false)
	 *
	 * @return  string
	 */
	public function getMediaLink()
	{
		if ($this->media_link === false)
		{
			$this->media_link = $this->getLink(false);
		}

		return $this->media_link;
	}

	/**
	 * Returns the media_thumb and caches the result
	 *
	 * @see getLink(true)
	 *
	 * @return  string
	 */
	public function getThumbLink()
	{
		if ($this->thumb_link === false)
		{
			$this->thumb_link = $this->getLink(true);
		}

		return $this->thumb_link;
	}

	/**
	 * Processes a string to be safe for HTML
	 *
	 * @param  string  $string  The string to escape
	 *
	 * @return  string  The escaped string
	 */
	public static function process($string)
	{
		return htmlentities(@iconv('UTF-8', 'UTF-8//IGNORE', $string));
	}

	/**
	 * Returns the filename escaped for HTML display and caches the result
	 *
	 * @return type
	 */
	public function getMediaFilenameProcessed()
	{
		if ( ! isset($this->media_filename_processed))
		{
			$this->media_filename_processed = static::process($this->media_filename);
		}

		return $this->media_filename_processed;
	}

	/**
	 * Get the path to the media. It doesn't check if the path exists
	 *
	 * @param  boolean  $thumbnail  True we're looking for a thumbnail, false if we're looking for a full media
	 * @param  boolean  $strict     If we want strictly the OP or the reply media. If false thumbnails will use either OP or reply as fallback.
	 *
	 * @return  null|string  Null if a dir can't be created, string if successful
	 */
	public function getDir($thumbnail = false, $strict = false)
	{
		if ($thumbnail === true)
		{
			if ($this->op)
			{
				if ($strict)
				{
					$image = $this->preview_op;
				}
				else
				{
					$image = $this->preview_op !== null ? $this->preview_op : $this->preview_reply;
				}
			}
			else
			{
				if ($strict)
				{
					$image = $this->preview_reply;
				}
				else
				{
					$image = $this->preview_reply !== null ? $this->preview_reply : $this->preview_op;
				}
			}
		}
		else
		{
			$image = $this->media;
		}

		if ($image === null)
		{
			return null;
		}

		return \Preferences::get('fu.boards.directory').'/'.$this->radix->shortname.'/'
			.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
	}


	/**
	 * Get the full URL to the media, and in case switch between multiple CDNs
	 *
	 * @param  boolean  $thumbnail  True if looking for a thumbnail, false for full media
	 *
	 * @return  null|string  Null if not available, string of the url if available
	 */
	public function getLink($thumbnail = false)
	{
		$before = \Foolz\Plugin\Hook::forge('foolfuuka\model\media.getLink.call.before')
			->setObject($this)
			->setParams(['thumbnail' => $thumbnail])
			->execute()
			->get();

		if ( ! $before instanceof \Foolz\Plugin\Void)
		{
			return $before;
		}

		// locate the image
		if ($thumbnail && file_exists($this->getDir($thumbnail)) !== false)
		{
			if ($this->op == 1)
			{
				$image = $this->preview_op ? : $this->preview_reply;
			}
			else
			{
				$image = $this->preview_reply ? : $this->preview_op;
			}
		}

		// full image
		if ( ! $thumbnail && file_exists($this->getDir(false)) !== false)
		{
			$image = $this->media;
		}

		// fallback if we have the full image but not the thumbnail
		if ($thumbnail && ! isset($image) && file_exists($this->getDir(false)))
		{
			$thumbnail = false;
			$image = $this->media;
		}

		if (isset($image))
		{
			$media_cdn = [];
			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && \Preferences::get('fu.boards.media_balancers_https'))
			{
				$balancers = \Preferences::get('fu.boards.media_balancers_https');
			}

			if (!isset($balancers) && \Preferences::get('fu.boards.media_balancers'))
			{
				$balancers = \Preferences::get('fu.boards.media_balancers');
			}

			if (isset($balancers))
			{
				$media_cdn = array_filter(preg_split('/\r\n|\r|\n/', $balancers));
			}

			if ( ! empty($media_cdn) && $this->media_id > 0)
			{
				return $media_cdn[($this->media_id % count($media_cdn))].'/'.$this->radix->shortname.'/'
					.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
			}

			return \Preferences::get('fu.boards.url').'/'.$this->radix->shortname.'/'
				.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
		}

		if ($thumbnail && $this->media_status === 'normal')
		{
			$this->media_status = 'not-available';
		}

		return null;
	}

	/**
	 * Get the remote link for media if it's not local
	 *
	 * @return  null|string  remote URL of local URL if not compatible with remote URL (see getLink() for return values)
	 */
	public function getRemoteLink()
	{
		if ($this->radix->archive && ($this->radix->images_url === false || $this->radix->images_url !== ""))
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $this->radix->images_url.$this->media_orig;
			}

			return \Uri::create([$this->radix->shortname, 'redirect']).$this->media_orig;
		}
		else
		{
			if (file_exists($this->getDir()) !== false)
			{
				return $this->getLink();
			}
		}
	}

	/**
	 * Encode or decode the hash from and to a safe URL representation
	 *
	 * @param  boolean  $urlsafe  True if we want the result to be URL-safe, false for standard MD5
	 *
	 * @return  string  The hash
	 */
	public function getHash($urlsafe = false)
	{
		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === true)
		{
			return static::urlsafe_b64encode(static::urlsafe_b64decode($this->media_hash));
		}
		else
		{
			return base64_encode(static::urlsafe_b64decode($this->media_hash));
		}
	}

	/**
	 * Encodes a media hash to base64 and converts eventual unsafe characters to safe ones
	 *
	 * @param  string  $string  The hash
	 *
	 * @return  string  URL-safe hash
	 */
	public static function urlsafe_b64encode($string)
	{
		$string = base64_encode($string);
		return str_replace(['+', '/', '='], ['-', '_', ''], $string);
	}

	/**
	 * Decodes a media hash and converts eventual safe characters to the original representation
	 *
	 * @param  string  $string  The media_hash
	 *
	 * @return  string  The original media hash
	 */
	public static function urlsafe_b64decode($string)
	{
		$string = str_replace(['-', '_'], ['+', '/'], $string);
		return base64_decode($string);
	}

	/**
	 * Deletes the media file if there's no other occurencies for the same file
	 *
	 * @param  boolean  $full   True if the full media should be deleted
	 * @param  boolean  $thumb  True if the thumbnail should be deleted (both OP and reply thumbnail will be deleted)
	 * @param  boolean  $purge  True if the image should be deleted regardless if total > 1 (needs 'comment.passwordless_deletion' powers)
	 */
	public function p_delete($full = true, $thumb = true, $purge = false)
	{
		// delete media file only if there is only one image OR the image is banned
		if ($this->total == 1 || $this->banned == 1 || (\Auth::has_access('comment.passwordless_deletion') && $purge))
		{
			if ($full === true)
			{
				$media_file = $this->getDir();

				if ($media_file !== null && file_exists($media_file))
				{
					unlink($media_file);
				}
			}

			if ($thumb === true)
			{
				$temp = $this->op;

				// remove OP thumbnail
				$this->op = 1;

				$thumb_file = $this->getDir(true);

				if ($thumb_file !== null && file_exists($thumb_file))
				{
					unlink($thumb_file);
				}

				// remove reply thumbnail
				$this->op = 0;
				$thumb_file = $this->getDir(true);

				if ($thumb_file !== null && file_exists($thumb_file))
				{
					unlink($thumb_file);
				}

				$this->op = $temp;
			}
		}
	}


	/**
	 * Bans an image for a board or for all the boards
	 *
	 * @param  boolean  $global  False if the media should be banned only on current Radix, true if it should be banned on all the Radix and future ones
	 */
	public function p_ban($global = false)
	{
		if ($global === false)
		{
			\DC::qb()
				->update($this->radix->getTable('_images'))
				->set('banned', true)
				->where('media_id = :media_id')
				->setParameter(':media_id', $this->media_id)
				->execute();

			$this->delete(true, true, true);
			return;
		}

		$result = \DC::qb()
			->select('COUNT(*) as count')
			->from($this->radix->getTable('_images'), 'ri')
			->where('media_hash = :md5')
			->setParameter(':md5', $this->media_hash)
			->execute()
			->fetch();

		if ( ! $result['count'])
		{
			\DC::forge()
				->insert('banned_md5', ['md5' => $this->media_hash])
				->execute();
		}

		foreach (\Radix::getAll() as $radix)
		{
			try
			{
				$media = \Media::getByMediaHash($radix, $this->media_hash);
				\DC::qb()
					->update($radix->getTable('_images'))
					->set('banned', true)
					->where('media_id = :media_id')
					->setParameter(':media_id', $media->media_id)
					->execute();

				$media->delete(true, true, true);
			}
			catch (MediaNotFoundException $e)
			{
				\DC::forge()
					->insert($radix->getTable('_images'), ['media_hash' => $this->media_hash, 'banned' => 1]);
			}
		}
	}

	/**
	 * Inserts the media uploaded (static::forgeFromUpload())
	 *
	 * @param  string   $microtime  The time in microseconds to use for the local filename
	 * @param  boolean  $is_op      True if the thumbnail sizes should be for OP, false if they should be for a reply
	 *
	 * @return  \Foolz\Foolfuuka\Model\Media       The current object updated with the insert data
	 * @throws  MediaInsertInvalidFormatException  In case the media is not a valid format
	 * @throws  MediaInsertDomainException         In case the media uploaded is in example too small or validations don't pass
	 * @throws  MediaInsertRepostException         In case the media has been reposted too recently according to control panel settings
	 */
	public function p_insert($microtime, $is_op)
	{
		$this->op = $is_op;
		$full_path = $this->temp_path.$this->temp_filename;

		$getimagesize = getimagesize($full_path);

		if (!$getimagesize)
		{
			throw new MediaInsertInvalidFormatException(__('The file you uploaded is not an image.'));
		}

		// if width and height are lower than 25 reject the image
		if ($getimagesize[0] < 25 || $getimagesize[1] < 25)
		{
			throw new MediaInsertDomainException(__('The image you uploaded is too small.'));
		}

		$this->media_w = $getimagesize[0];
		$this->media_h = $getimagesize[1];
		$this->media_orig = $microtime.'.'.strtolower($this->temp_extension);
		$this->preview_orig = $microtime.'s.'.strtolower($this->temp_extension);
		$this->media_hash = base64_encode(pack("H*", md5(file_get_contents($full_path))));

		$do_thumb = true;
		$do_full = true;

		try
		{
			$duplicate = static::getByMediaHash($this->radix, $this->media_hash);

			// we want the current media to work with the same filenames as previously stored
			$this->media_id = $duplicate->media_id;
			$this->media = $duplicate->media;
			$this->preview_op = $duplicate->preview_op;
			$this->preview_reply = $duplicate->preview_reply;

			if ($this->radix->min_image_repost_time)
			{
				// if it's -1 it means that image reposting is disabled, so this image shouldn't pass
				if ($this->radix->min_image_repost_time == -1)
				{
					throw new MediaInsertRepostException(
						__('This image has already been posted once. This board doesn\'t allow image reposting.')
					);
				}

				// we don't have to worry about archives with weird timestamps, we can't post images there
				$duplicate_entry = \DC::qb()
					->select('COUNT(*) as count, MAX(timestamp) as max_timestamp')
					->where('media_id = :media_id')
					->andWhere('timestamp > :timestamp')
					->setParameter('media_id', $duplicate->media_id)
					->setParameter('timestamp', time() - $this->radix->min_image_repost_time)
					->setMaxResults(1)
					->execute()
					->fetch();

				if ($duplicate_entry['count'])
				{
					$datetime = new \DateTime(date('Y-m-d H:i:s', $duplicate_entry['max_timestamp'] + $this->radix->min_image_repost_time));
					$remain = $datetime->diff(new \DateTime());

					throw new MediaInsertRepostException(
						\Str::tr(
							__('This image has been posted recently. You will be able to post it again in :time.'),
							['time' =>
								 ($remain->d > 0 ? $remain->d.' '.__('day(s)') : '').' '
								.($remain->h > 0 ? $remain->h.' '.__('hour(s)') : '').' '
								.($remain->i > 0 ? $remain->i.' '.__('minute(s)') : '').' '
								.($remain->s > 0 ? $remain->s.' '.__('second(s)') : '')]
						)
					);
				}
			}

			// if we're here, we got the media
			$duplicate_dir = $duplicate->getDir();
			if ($duplicate_dir !== null && file_exists($duplicate_dir))
			{
				$do_full = false;
			}

			$duplicate->op = $is_op;
			$duplicate_dir_thumb = $duplicate->getDir(true, true);
			if ($duplicate_dir_thumb !== null && file_exists($duplicate_dir_thumb))
			{
				$duplicate_dir_thumb_size = getimagesize($duplicate_dir_thumb);
				$this->preview_w = $duplicate_dir_thumb_size[0];
				$this->preview_h = $duplicate_dir_thumb_size[1];
				$do_thumb = false;
			}
		}
		catch (MediaNotFoundException $e)
		{

		}

		if ($do_thumb)
		{
			$thumb_width = $this->radix->thumbnail_reply_width;
			$thumb_height = $this->radix->thumbnail_reply_height;

			if ($is_op)
			{
				$thumb_width = $this->radix->thumbnail_op_width;
				$thumb_height = $this->radix->thumbnail_op_height;
			}

			if ( ! file_exists($this->pathFromFilename(true, $is_op)))
			{
				mkdir($this->pathFromFilename(true, $is_op), 0777, true);
			}

			$return = \Foolz\Plugin\Hook::forge('fu.model.media.insert.resize')
				->setObject($this)
				->setParams([
					'thumb_width' => $thumb_width,
					'thumb_height' => $thumb_height,
					'full_path' => $full_path,
					'is_op' => $is_op
				])
				->execute()
				->get();

			if ($return instanceof \Foolz\Plugin\Void)
			{
				if ($this->radix->enable_animated_gif_thumbs && strtolower($this->temp_extension) === 'gif')
				{
					exec("convert ".$full_path." -coalesce -treedepth 4 -colors 256 -quality 80 -background none ".
						"-resize \"".$thumb_width."x".$thumb_height.">\" ".$this->pathFromFilename(true, $is_op, true));
				}
				else
				{
					exec("convert ".$full_path."[0] -quality 80 -background none ".
						"-resize \"".$thumb_width."x".$thumb_height.">\" ".$this->pathFromFilename(true, $is_op, true));
				}
			}

			$thumb_getimagesize = getimagesize($this->pathFromFilename(true, $is_op, true));
			$this->preview_w = $thumb_getimagesize[0];
			$this->preview_h = $thumb_getimagesize[1];
		}

		if ($do_full)
		{
			if (!file_exists($this->pathFromFilename()))
			{
				mkdir($this->pathFromFilename(), 0777, true);
			}

			copy($full_path, $this->pathFromFilename(false, false, true));
		}

		if (function_exists('exif_read_data') && in_array(strtolower($this->temp_extension), ['jpg', 'jpeg', 'tiff']))
		{
			$media_data = null;
			getimagesize($full_path, $media_data);

			if ( ! isset($media_data['APP1']) || strpos($media_data['APP1'], 'Exif') === 0)
			{
				$exif = exif_read_data($full_path);

				if ($exif !== false)
				{
					$this->exif = $exif;
				}
			}
		}

		if ( ! $this->media_id)
		{
			 \DC::forge()->insert($this->radix->getTable('_images'), [
				'media_hash' => $this->media_hash,
				'media' => $this->media_orig,
				'preview_op' => $this->op ? $this->preview_orig : null,
				'preview_reply' => ! $this->op ? $this->preview_orig : null,
				'total' => 1,
				'banned' => false,
			]);

			$this->media_id = \DC::forge()->lastInsertId();
		}
		else
		{
			$query = \DC::qb()
				->update($this->radix->getTable('_images'));
			if ($this->media === null)
			{
				$query->set('media', ':media_orig')
					->setParameter(':media_orig', $this->preview_orig);
			}
			if ($this->op && $this->preview_op === null)
			{
				$query->set('preview_op', ':preview_orig')
				->setParameter(':preview_orig', $this->preview_orig);
			}
			if ( ! $this->op && $this->preview_reply === null)
			{
				$query->set('preview_reply', ':preview_orig')
				->setParameter(':preview_orig', $this->preview_orig);
			}
			$query->set('total', 'total + 1');

			$query->where('media_id = :media_id')
				->setParameter(':media_id', $this->media_id)
				->execute();
		}

		return $this;
	}

	/**
	 * Creates a path for the file
	 *
	 * @param  boolean  $thumbnail      If the path should be for a thumbnail
	 * @param  boolean  $is_op          If the path should be for an OP
	 * @param  boolean  $with_filename  If the path should include the filename
	 *
	 * @return  string  The path
	 */
	public function p_pathFromFilename($thumbnail = false, $is_op = false, $with_filename = false)
	{
		$dir = \Preferences::get('fu.boards.directory').'/'.$this->radix->shortname.'/'.
			($thumbnail ? 'thumb' : 'image').'/';

		// we first check if we have media/preview_op/preview_reply available to reuse the value
		if ($thumbnail)
		{
			if ($is_op && $this->preview_op !== null)
			{
				return $dir.'/'.substr($this->preview_op, 0, 4).'/'.substr($this->preview_op, 4, 2).'/'.
					($with_filename ? $this->preview_op : '');
			}
			else if ( ! $is_op && $this->preview_reply !== null)
			{
				return $dir.'/'.substr($this->preview_reply, 0, 4).'/'.substr($this->preview_reply, 4, 2).'/'.
					($with_filename ? $this->preview_reply : '');
			}

			// we didn't have media/preview_op/preview_reply so fallback to making a new file
			return $dir.'/'.substr($this->preview_orig, 0, 4).'/'.substr($this->preview_orig, 4, 2).'/'.
				($with_filename ? $this->preview_orig : '');
		}
		else
		{
			if ($this->media !== null)
			{
				return $dir.'/'.substr($this->media, 0, 4).'/'.substr($this->media, 4, 2).'/'.
					($with_filename ? $this->media : '');
			}

			// we didn't have media/preview_op/preview_reply so fallback to making a new file
			return $dir.'/'.substr($this->media_orig, 0, 4).'/'.substr($this->media_orig, 4, 2).'/'.
				($with_filename ? $this->media_orig : '');
		}
	}
}