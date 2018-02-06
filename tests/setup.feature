Feature: Setup the system
  In order to setup the system
  As an user
  I want to access the setup page

  Scenario: User setups system for first time.
    Given I am on '/typemill'
      And in the settings the 'setup' is true
     When I go to '/typemill/setup'
     Then some testable outcome is achieved
	 
  Scenario: System has been setup already and user tries to reach setup page again.
    Given I am on '/typemill'
      And in the settings the 'setup' is false
     When I go to '/typemill/setup'
     Then I can read 'page not found'

  Scenario: System saves valid settings.
    Given I am on '/typemill/setup'
      And I enter '' to ''
     When I enter
     Then I am on '/typemill/welcome'

  Scenario: System saves invalid settings.
    Given I am on '/typemill/setup'
     When I enter
     Then I am on '/typemill/setup'