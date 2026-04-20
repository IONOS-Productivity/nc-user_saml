<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_SAML\Tests;

use OCA\User_SAML\Db\ConfigurationsMapper;
use OCA\User_SAML\SAMLSettings;
use OCP\IConfig;
use OCP\ISession;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SAMLSettingsTest extends TestCase {
	private IURLGenerator&MockObject $urlGenerator;
	private IConfig&MockObject $config;
	private ISession&MockObject $session;
	private ConfigurationsMapper&MockObject $mapper;
	private SAMLSettings $samlSettings;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->mapper = $this->createMock(ConfigurationsMapper::class);

		$this->samlSettings = new SAMLSettings(
			$this->urlGenerator,
			$this->config,
			$this->session,
			$this->mapper,
		);
	}

	public function testGetListOfConfiguredIdpsReturnsEmptyWhenNoIdpsExist(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([], $result);
	}

	public function testGetListOfConfiguredIdpsReturnsEmptyWhenEntityIdAndSsoUrlMissing(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([
				1 => [
					'general-idp0_display_name' => 'My IdP',
					// no idp-entityId, no idp-singleSignOnService.url
				],
			]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([], $result);
	}

	public function testGetListOfConfiguredIdpsReturnsEmptyWhenValuesAreOnlyWhitespace(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([
				1 => [
					'general-idp0_display_name' => 'My IdP',
					'idp-entityId' => '   ',
					'idp-singleSignOnService.url' => "\t",
				],
			]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([], $result);
	}

	public function testGetListOfConfiguredIdpsReturnsIdpWhenFullyConfigured(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([
				1 => [
					'general-idp0_display_name' => 'My IdP',
					'idp-entityId' => 'https://idp.example.com',
					'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
				],
			]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([1 => 'My IdP'], $result);
	}

	public function testGetListOfConfiguredIdpsFiltersOutPartiallyConfiguredIdps(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([
				1 => [
					'general-idp0_display_name' => 'Configured IdP',
					'idp-entityId' => 'https://idp.example.com',
					'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
				],
				2 => [
					'general-idp0_display_name' => 'Missing SSO URL',
					'idp-entityId' => 'https://idp2.example.com',
					// missing idp-singleSignOnService.url
				],
				3 => [
					'general-idp0_display_name' => 'Missing Entity ID',
					// missing idp-entityId
					'idp-singleSignOnService.url' => 'https://idp3.example.com/sso',
				],
			]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([1 => 'Configured IdP'], $result);
	}

	public function testGetListOfConfiguredIdpsUsesDisplayNameAsValue(): void {
		$this->mapper->expects($this->once())
			->method('getAll')
			->willReturn([
				1 => [
					'idp-entityId' => 'https://idp.example.com',
					'idp-singleSignOnService.url' => 'https://idp.example.com/sso',
					// no display name set
				],
			]);

		$result = $this->samlSettings->getListOfConfiguredIdps();

		$this->assertSame([1 => ''], $result);
	}
}
