//       The MIT License (MIT)
//
//  Copyright (c) 2013 Actual Reports
//
//  Permission is hereby granted, free of charge, to any person obtaining a copy of
//  this software and associated documentation files (the "Software"), to deal in
//  the Software without restriction, including without limitation the rights to
//  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
//  the Software, and to permit persons to whom the Software is furnished to do so,
//  subject to the following conditions:
//
//  The above copyright notice and this permission notice shall be included in all
//  copies or substantial portions of the Software.
//
//  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
//  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
//  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
//  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
//  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
//  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

import java.io.DataOutputStream;
import java.io.InputStream;
import java.io.UnsupportedEncodingException;
import java.net.URL;
import java.net.URLEncoder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.Map.Entry;
import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

public class ActualReportsApi {

	final String USER_AGENT = "actualreports-php/2.0.0";
	public String url = "https://dev.actualreports.com/api";
	public String version = "v2";
	public String apiKey;
	public String privateKey;
	public String email;

	// Create new instance with apiKey and privateKey
	public ActualReportsApi(String apiKey, String privateKey) {
		this.apiKey = apiKey;
		this.privateKey = privateKey;
	}

	// Create new instance without apiKey and priavteKey defined
	public ActualReportsApi() {
	}

	/**
	 * Set apikey value
	 * 
	 * @param apiKey
	 */
	public void setApiKey(String apiKey) {
		this.apiKey = apiKey;
	}

	/**
	 * Set privatekey value
	 * 
	 * @param privateKey
	 */
	public void setPrivateKey(String privateKey) {
		this.privateKey = privateKey;
	}

	/**
	 * Set api endpoint url
	 * 
	 * @param url
	 */
	public void setUrl(String url) {
		this.url = url;
	}

	/**
	 * Set user email
	 * 
	 * @param email
	 */
	public void setEmail(String email) {
		this.email = email;
	}

	/**
	 * Set version
	 * 
	 * @param version
	 *            v1 or v2
	 * 
	 */
	public void setVersion(String version) {
		this.version = version;
	}

	/**
	 * Makes request and returns the map of content, headers and responseCode
	 * 
	 * @param
	 * 
	 * @Return map["content"] --> content in bytecode map["headers"] --> map
	 *         of headers map["responseCode"] --> http response code
	 */
	public Map<String, Object> request(String resource) {
		return this.request(resource, null, "GET");
	}

	/**
	 * Makes request and returns the map of content, headers and responseCode
	 * 
	 * @param
	 * 
	 * @Return map["content"] --> content in bytecode map["headers"] --> map
	 *         of headers map["responseCode"] --> http response code
	 */
	public Map<String, Object> request(String resource, Map<String, ?> params) {
		return this.request(resource, params, "GET");
	}

	/**
	 * Makes request and returns the map of content, headers and responseCode
	 * 
	 * @param
	 * 
	 * @return map["content"] --> content in bytecode map["headers"] --> map
	 *         of headers map["responseCode"] --> http response code
	 */
	public Map<String, Object> request(String resource, Map<String, ?> params,
			String method) {
		
		String url = this.createUrl(resource);
		Map<String, Object> result = new HashMap<String, Object>();
		String query = "";
						
		if (params != null && !params.isEmpty())
			query = crateQueryString(params);

		// Send params with get method
		if (method.toLowerCase().equals("get") && query != "")
			url += "&" + query;

		try {
			// Create connection
			URL url1 = new URL(url);
			HttpsURLConnection con = (HttpsURLConnection) url1.openConnection();
			con.setInstanceFollowRedirects(false);
			con.setHostnameVerifier(new HostnameVerifier() {
				@Override
				public boolean verify(String arg0, SSLSession arg1) {
					return true;
				}
			});

			// Send data with post method
			if (method.toLowerCase().equals("post")) {
				con.setDoOutput(true);
				con.setDoInput(true);
				con.setRequestMethod("POST");
				con.setRequestProperty("Content-length",
						String.valueOf(query.length()));
				con.setRequestProperty("Content-Type",
						"application/x-www-form-urlencoded");
				con.setRequestProperty("User-Agent", this.USER_AGENT);

				DataOutputStream dos = new DataOutputStream(
						con.getOutputStream());
				dos.writeBytes(query);
				dos.flush();
				dos.close();
			}

			int responseCode = con.getResponseCode();
			Map headerMap = con.getHeaderFields();

			result.put("responseCode", responseCode);
			result.put("header", headerMap);

			ArrayList bytes = new ArrayList();
			int bit;

			// Unsuccessful attempt to get information => read errors
			if (responseCode != 200) {
				InputStream is = con.getErrorStream();
				while ((bit = is.read()) != -1) {
					bytes.add(bit);
				}
				is.close();
				result.put("content", bytes);

			} else { // Successful attempt
				InputStream is = con.getInputStream();
				while ((bit = is.read()) != -1) {
					bytes.add(bit);
				}
				is.close();
				result.put("content", bytes);
			}
		con.disconnect();

		} catch (Exception e) {
			e.printStackTrace();
		}

		return result;
	}

	/**
	 * Returns url for editor redirect
	 * 
	 * @return string
	 */

	public String getEditorUrl() {
		return this.createUrl("editor");
	}

	private String createUrl(String resource) {
		long time = System.currentTimeMillis();

		Map<String, Object> map = new HashMap<String, Object>();
		map.put("apikey", this.apiKey);
		map.put("email", this.email);
		map.put("signature", this.createSignature(resource, time));
		map.put("timestamp", time);
		String query = crateQueryString(map);
		return (this.url + "/" + this.version + "/" + resource).replaceAll(
				"([a-zA-Z0-9])[/]+", "$1/") + "?" + query;
	}

	private String createSignature(String resource, long time) {

		if (this.apiKey == null) {
			try {
				throw new Exception("Missing apikey!");
			} catch (Exception e) {
				e.printStackTrace();
			}
		}

		if (this.privateKey == null) {
			try {
				throw new Exception("Missing privateKey!");
			} catch (Exception e) {
				e.printStackTrace();
			}
		}

		try {
			MessageDigest digest = MessageDigest.getInstance("SHA-256");
			String toHash = this.privateKey.concat(this.apiKey)
					.concat(resource.toLowerCase().replace("/", ""))
					.concat(this.email).concat(String.valueOf(time));

			return bytesToHex(digest.digest(toHash.getBytes()));

		} catch (NoSuchAlgorithmException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

		return null;
	}

	private String bytesToHex(byte[] bytes) {
		StringBuffer result = new StringBuffer();
		for (byte byt : bytes)
			result.append(Integer.toString((byt & 0xff) + 0x100, 16).substring(
					1));
		return result.toString();
	}

	/**
	 * Converts map to query
	 * 
	 * @param querymap
	 * @return queryString
	 */

	private String crateQueryString(Map<String, ?> querymap) {

		StringBuilder result = new StringBuilder();

		for (Entry<String, ?> entry : querymap.entrySet()) {
			if (result.length() > 0) {
				result.append("&");
			}
			try {
				result.append(String.format("%s=%s", urlEncodeUTF8(entry
						.getKey().toString()), urlEncodeUTF8(entry.getValue()
						.toString())));
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
		return result.toString();
	}

	/**
	 * Convert String to UTF-8
	 * 
	 * @param s
	 * @return string
	 */

	private String urlEncodeUTF8(String s) {
		try {
			return URLEncoder.encode(s, "UTF-8");
		} catch (UnsupportedEncodingException e) {
			throw new UnsupportedOperationException(e);
		}
	}

}
