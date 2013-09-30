	# The MIT License (MIT)
#
#  Copyright (c) 2013 Actual Reports
#
#  Permission is hereby granted, free of charge, to any person obtaining a copy of
#  this software and associated documentation files (the "Software"), to deal in
#  the Software without restriction, including without limitation the rights to
#  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
#  the Software, and to permit persons to whom the Software is furnished to do so,
#  subject to the following conditions:
#
#  The above copyright notice and this permission notice shall be included in all
#  copies or substantial portions of the Software.
#
#  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
#  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
#  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
#  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
#  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require 'Digest'
require 'net/http'
require 'OpenSSL'
require 'json'

class ActualReportsException < Exception
end

class ActualReportsApi
	USER_AGENT = 'actualreports-ruby/2.0.0'

	def initialize(apiKey = nil, privateKey = nil)
		@url = 'https://dev.actualreports.com/api'
		@version = 'v2'
		@apiKey
		@privateKey
		@email
		if(apiKey) then @apiKey = apiKey end
		if(privateKey) then @privateKey = privateKey end
	end

=begin
   * Set apikey value
   *
   * @param string apiKey
=end

	def setApiKey(apiKey)
		@apiKey = apiKey
	end

=begin
   * Set privatekey value
   *
   * @param string privatKey
=end
	def setPrivateKey(privateKey)
		@privateKey = privateKey
	end

=begin
   * Set api endpoint url
   *
   * @param string url
=end

	def setUrl(url)
	@url = url
	end

=begin
   * Set user email
   *
   * @param string email
=end
	def setEmail(email)
	@email = email
	end

=begin
   * Set version
   * @param string version v1 or v2
=end
	def setVersion(version)
	@version = version
	end

=begin
   * Returns url for editor redirect
   *
   * @return string
=end

	def getEditorUrl()
		return createUrl('editor')
	end


	def request(resource,params = {}, method='get', stripMeta = true)
		code, headers, content = makeRequest(createUrl(resource), params, method)
		if(resource == 'editor' and !valid_json? content)  then
			return getEditorUrl()
		elsif(!valid_json? content)
			return content
		else
			content = JSON.parse(content)
			if(code != '200') then raise ActualReportsException.new(content['error'] ? content['error'] : 'Service error') end
		end
		return stripMeta ? content['response'] : content
	end

	def valid_json? json_
			JSON.parse(json_)
		return true
			rescue JSON::ParserError
		return false
	end

	protected
	def createSignature(resource, time)
	if(!@privateKey) then raise ActualReportsException.new('Missing privatekey') end
	if(!@apiKey) then raise ActualReportsException.new('Missing apikey') end
	if(!@email) then raise ActualReportsException.new('Missing email') end
	return Digest::SHA256.new.update(@privateKey+@apiKey+(resource.gsub('/','').downcase)+@email+time).hexdigest
	end

	def createUrl(resource)
		time = Time.now.to_i.to_s
		query = URI.encode_www_form({
			'apikey' => @apiKey,
			'email' => @email,
			'signature' => createSignature(resource,time),
			'timestamp' => time
		})
		return [@url,@version,resource].join('/').gsub(/([a-zA-Z])[\/]+/, '\1/') << "?#{query}"
	end

	#Makes request and returns the result body + headers + (error codes)
	def makeRequest(url, params = {}, method = 'get')
		if(params and method == 'get') then url = url << "&" << URI.encode_www_form(params) end
		uri = URI.parse(url)
		handle = Net::HTTP.new(uri.host,uri.port)
		handle.use_ssl = true
		handle.verify_mode = OpenSSL::SSL::VERIFY_NONE
		header = {'Content-Type' =>'application/json',
		'Accept-Charset' => 'utf-8',
		'User-Agent' => USER_AGENT}

		case method.downcase
			when 'post'
			request = Net::HTTP::Post.new(uri.request_uri,header)
			if(params) then request.set_form_data(params) end
			when 'get'
			request = Net::HTTP::Get.new(uri.request_uri,header)
		end

		response = handle.request(request)
		return  response.code, response.to_hash, response.body
	end
end