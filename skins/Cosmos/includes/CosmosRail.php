<?php

namespace MediaWiki\Skin\Cosmos;

use DateInterval;
use DateTime;
use Html;
use IContextSource;
use MediaWiki\MediaWikiServices;
use Title;

class CosmosRail {
	/** @var CosmosConfig */
	private $config;

	/** @var IContextSource */
	private $contextSource;

	/** @var string */
	private static $railHookContents = '';

	/**
	 * @param CosmosConfig $config
	 * @param IContextSource $contextSource
	 */
	public function __construct(
		CosmosConfig $config,
		IContextSource $contextSource
	) {
		$this->config = $config;
		$this->contextSource = $contextSource;

		if ( !(bool)static::$railHookContents ) {
			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'CosmosRail', [ $this, $contextSource->getSkin() ] );
		}
	}

	/**
	 * @param CosmosConfig $config
	 * @param IContextSource $contextSource
	 * @return bool
	 */
	public static function railsHidden(
		CosmosConfig $config,
		IContextSource $contextSource
	) {
		$blacklistedNamespaces = $config->getRailBlacklistedNamespaces();
		$blacklistedPages = $config->getRailBlacklistedPages();

		$title = $contextSource->getTitle();

		if (
			$title->inNamespaces( $blacklistedNamespaces ) ||
			(
				$title->isMainPage() &&
				in_array( 'mainpage', $blacklistedPages )
			) ||
			in_array( $title->getFullText(), $blacklistedPages ) ||
			$blacklistedPages === [ '{$CURRENTPAGE}' ]
		) {
			return true;
		}

		return false;
	}

	/**
	 * @param CosmosConfig $config
	 * @param IContextSource $contextSource
	 * @return bool
	 */
	public static function railsExist(
		CosmosConfig $config,
		IContextSource $contextSource
	) {
		$validModules = [ 'interface', 'recentchanges' ];
		$enabledModules = [];

		foreach ( $config->getEnabledRailModules() as $module => $value ) {
			if ( $value ) {
				$enabledModules[] = $module;
			}
		}

		$moduleCount = 0;

		$interfaceRailModules = $config->getEnabledRailModules()['interface'];

		$interfaceModules = $interfaceRailModules[0] ?? $interfaceRailModules;

		foreach ( (array)$interfaceModules as $message => $type ) {
			if ( $type && !$contextSource->msg( $message )->isDisabled() ) {
				$moduleCount++;
			}
		}

		if ( !array_intersect( $validModules, $enabledModules ) ||
			( $enabledModules === [ 'interface' ] &&
				$moduleCount === 0 ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param self $self
	 * @param IContextSource $contextSource
	 * @return bool
	 */
	public static function hookRailsExist(
		self $self,
		IContextSource $contextSource
	) {
		if ( !(bool)static::$railHookContents ) {
			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->run( 'CosmosRail', [ $self, $contextSource->getSkin() ] );
		}

		if ( !(bool)static::$railHookContents ) {
			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function buildRail() {
		if ( ( !self::railsExist( $this->config, $this->contextSource ) &&
			!self::hookRailsExist( $this, $this->contextSource ) ) ||
				self::railsHidden( $this->config, $this->contextSource )
		) {
			return '';
		}

		$html = Html::openElement( 'div', [
				'class' => 'CosmosRail',
				'id' => 'CosmosRailWrapper'
			]
		);

		$html .= Html::openElement( 'div', [
				'class' => 'cosmos-rail-inner',
				'id' => 'CosmosRail'
			]
		);

		if ( (bool)static::$railHookContents ) {
			$html .= static::$railHookContents;
		}

		foreach ( (array)$this->getInterfaceModules() as $message => $type ) {
			if ( $type === 'sticky' ) {
				$html .= Html::rawElement( 'section', [
						'class' => 'railModule module rail-sticky-module interface-module'
					], $this->contextSource->msg( $message )->parse()
				);
			} else {
				$html .= Html::rawElement( 'section', [
						'class' => 'railModule module interface-module'
					], $this->contextSource->msg( $message )->parse()
				);
			}
		}

		$enableRecentChangesModule = $this->config->getEnabledRailModules()['recentchanges'];
		if ( !empty( $this->getRecentChanges() ) && $enableRecentChangesModule ) {
			$html .= $this->getRecentChangesModule();
		}

		$html .= Html::closeElement( 'div' );
		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * @param string $body
	 * @param string $header
	 * @param string $type
	 * @param string $class
	 */
	public function buildModule(
		string $body,
		string $header = '',
		string $type = 'normal',
		string $class = 'custom-module'
	) {
		if ( $type === 'sticky' ) {
			static::$railHookContents .= Html::openElement( 'section', [
					'class' => "railModule module rail-sticky-module {$class}"
				]
			);
		} else {
			static::$railHookContents .= Html::openElement( 'section', [
					'class' => "railModule module {$class}"
				]
			);
		}

		if ( $header ) {
			static::$railHookContents .= $this->buildModuleHeader( $header );
		}

		static::$railHookContents .= $body;

		static::$railHookContents .= Html::closeElement( 'section' );
	}

	/**
	 * @param string $label
	 * @return string
	 */
	protected function buildModuleHeader( string $label ) {
		if ( !$this->contextSource->msg( $label )->isDisabled() ) {
			$label = $this->contextSource->msg( $label )->text();
		}

		$html = Html::element( 'h3', [], $label );

		return $html;
	}

	/**
	 * @return array
	 */
	protected function getInterfaceModules() {
		$modules = [];

		$interfaceRailModules = $this->config->getEnabledRailModules()['interface'];

		$interfaceModules = $interfaceRailModules[0] ?? $interfaceRailModules;

		foreach ( (array)$interfaceModules as $message => $type ) {
			if ( $type && !$this->contextSource->msg( $message )->isDisabled() ) {
				$modules += [ $message => $type ];
			}
		}

		return $modules;
	}

	/**
	 * @return string
	 */
	protected function getRecentChangesModule() {
		$currentTime = DateTime::createFromFormat( 'YmdHis', wfTimestampNow() );
		$type = $this->config->getEnabledRailModules()['recentchanges'];

		if ( $type === 'sticky' ) {
			$html = Html::openElement( 'section', [
					'class' => 'railModule module rail-sticky-module recentchanges-module'
				]
			);
		} else {
			$html = Html::openElement( 'section', [
					'class' => 'railModule module recentchanges-module'
				]
			);
		}

		$html .= $this->buildModuleHeader( 'recentchanges' );

		foreach ( $this->getRecentChanges() as $recentChange ) {
				// Get the time the edit was made
				$time = DateTime::createFromFormat( 'YmdHis', $recentChange['timestamp'] );

				// Get a string representing the time difference
				$timeDiff = $this->getDateTimeDiffString( $currentTime->diff( $time ) );

				// Get the title of the page that was edited
				$page = Title::newFromText( $recentChange['title'], $recentChange['namespace'] );

				// Get the title of the userpage of the user who edited it
				$user = Title::newFromText( $recentChange['user'], NS_USER );

				// Open list item for recent change
				$html .= Html::openElement( 'li' );

				$html .= Html::openElement( 'div', [ 'class' => 'cosmos-recentChanges-page' ] );

				// Create a link to the edited page
				$html .= Html::openElement( 'a', [ 'href' => $page->getInternalURL() ] );
				$html .= $page->getFullText();
				$html .= Html::closeElement( 'a' );

				$html .= Html::closeElement( 'div' );

				$html .= Html::openElement( 'div', [ 'class' => 'cosmos-recentChanges-info' ] );

				// Create a link to the user who edited it
				$html .= Html::openElement( 'a', [ 'href' => $user->getInternalURL() ] );
				$html .= $user->getText();
				$html .= Html::closeElement( 'a' );

				// Display how long ago it was edited
				$html .= ' • ';
				$html .= $timeDiff;

				$html .= Html::closeElement( 'div' );

				// Close the list item
				$html .= Html::closeElement( 'li' );
		}

		$html .= Html::closeElement( 'section' );

		return $html;
	}

	/**
	 * @return array
	 */
	protected static function getRecentChanges() {
		$cacheObj = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cacheKey = $cacheObj->makeKey( 'cosmos_recentChanges', 4 );
		$recentChanges = $cacheObj->get( $cacheKey );

		if ( empty( $recentChanges ) ) {
			$database = wfGetDB( DB_REPLICA );
			$recentChangesTable = $database->tableName( 'recentchanges' );

			$rawRecentChanges = $database->select(
				'recentchanges',
				[
					'rc_timestamp', 'rc_actor', 'rc_namespace',
					'rc_title', 'rc_type',
				],
				[
					'rc_bot <> 1',
					'rc_type <> ' . RC_EXTERNAL,
					'rc_type <> ' . RC_LOG,
					"rc_id IN (SELECT MAX(rc_id) FROM {$recentChangesTable} GROUP BY rc_namespace, rc_title)"
				],
				__METHOD__,
				[ 'ORDER BY' => 'rc_id DESC', 'LIMIT' => 4, 'OFFSET' => 0 ]
			);

			$actors = [];

			$recentChanges = [];

			foreach ( $rawRecentChanges as $recentChange ) {
				$actorId = $recentChange->rc_actor;
				$actor = $actors[$actorId] ?? '';

				if ( empty( $actor ) ) {
					$actorRaw = $database->selectRow(
						'actor',
						[ 'actor_user', 'actor_name' ],
						[ 'actor_id' => $recentChange->rc_actor ],
						__METHOD__
					);

					$actor = [];
					$actor['name'] = $actorRaw->actor_name;
					$actor['anon'] = empty( $actorRaw->actor_user );

					$actors[$actorId] = $actor;
				}

				$recentChanges[] = [
					'timestamp' => $recentChange->rc_timestamp,
					'user' => $actor['name'],
					'anon' => $actor['anon'],
					'namespace' => $recentChange->rc_namespace,
					'title' => $recentChange->rc_title,
					'type' => $recentChange->rc_type
				];
			}

			$cacheObj->set( $cacheKey, $recentChanges, 30 );
		}

		return $recentChanges;
	}

	/**
	 * @param DateInterval $interval
	 * @return string
	 */
	protected function getDateTimeDiffString( DateInterval $interval ) {
		if ( $interval->y > 0 ) {
			$msg = $this->contextSource->msg( 'years', $interval->y );
		} elseif ( $interval->m > 0 ) {
			$msg = $this->contextSource->msg( 'months', $interval->m );
		} elseif ( $interval->d > 7 ) {
			$msg = $this->contextSource->msg( 'weeks', floor( $interval->d / 7 ) );
		} elseif ( $interval->d > 0 ) {
			$msg = $this->contextSource->msg( 'days', $interval->d );
		} elseif ( $interval->h > 0 ) {
			$msg = $this->contextSource->msg( 'hours', $interval->h );
		} elseif ( $interval->i > 0 ) {
			$msg = $this->contextSource->msg( 'minutes', $interval->i );
		} else {
			$msg = $this->contextSource->msg( 'seconds', $interval->s );
		}

		return $this->contextSource->msg( 'ago', $msg );
	}
}
