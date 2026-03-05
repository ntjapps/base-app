<?php

describe('Public Landing Routes', function () {
    it('serves landing page', function () {
        $this->get(route('landing-page'))->assertStatus(200);
    });

    it('serves privacy policy pages', function () {
        $this->get(route('privacy-policy'))->assertStatus(200);
        $this->get(route('privacy-policy-waagent'))->assertStatus(200);
    });

    it('serves terms of service pages', function () {
        $this->get(route('terms-of-service'))->assertStatus(200);
        $this->get(route('terms-of-service-waagent'))->assertStatus(200);
    });
});
