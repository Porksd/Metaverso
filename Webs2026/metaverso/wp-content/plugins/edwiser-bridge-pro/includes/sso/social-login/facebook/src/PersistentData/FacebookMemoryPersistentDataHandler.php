<?php
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Facebook\PersistentData;

/**
 * Class FacebookMemoryPersistentDataHandler
 *
 * @package Facebook
 */
class FacebookMemoryPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var array The session data to keep in memory.
     */
    protected $sessionData = [];

    /**
     * @inheritdoc
     */
    public function get( $key ) {
        global $eb_user_id;
        if ( empty( $eb_user_id ) ) {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//check ip from share internet
				$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip_addr = $_SERVER['REMOTE_ADDR'];
			}
			$eb_user_id = get_transient('eb_user_' . $ip_addr . '_eb_user_id');
            if ( empty( $eb_user_id ) ) {
                $eb_user_id = bin2hex(openssl_random_pseudo_bytes(16));
            }
            set_transient(
                'eb_user_' . $ip_addr . '_eb_user_id',
                $eb_user_id,
                HOUR_IN_SECONDS
            );
		}
        $fb_session_data = maybe_unserialize( get_option( $eb_user_id ) );
        if ( ! is_array( $fb_session_data ) ) {
            $fb_session_data = array();
        }

        if ( isset( $fb_session_data[ $key ] ) ) {
            return $fb_session_data[ $key ];
        }

        return false;
        // return isset($this->sessionData[$key]) ? $this->sessionData[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function set( $key, $value ) {
        global $eb_user_id;
        if ( empty( $eb_user_id ) ) {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//check ip from share internet
				$ip_addr = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				$ip_addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip_addr = $_SERVER['REMOTE_ADDR'];
			}
			$eb_user_id = get_transient('eb_user_' . $ip_addr . '_eb_user_id');
            if ( empty( $eb_user_id ) ) {
                $eb_user_id = bin2hex(openssl_random_pseudo_bytes(16));
            }
            set_transient(
                'eb_user_' . $ip_addr . '_eb_user_id',
                $eb_user_id,
                HOUR_IN_SECONDS
            );
		}

        // get option value and then update it.
        $fb_session_data         = maybe_unserialize( get_option( $eb_user_id ) );
        if ( ! is_array( $fb_session_data ) ) {
            $fb_session_data = array();
        }
        $fb_session_data[ $key ] = $value;
        $fb_session_data         = serialize( $fb_session_data );

        // update option with key as session id and data as value
        update_option( $eb_user_id, $fb_session_data );

        // $this->sessionData[$key] = $value;
    }
}