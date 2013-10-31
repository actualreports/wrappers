import java.io.DataOutputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

public class Main {

	public static void main(String[] args) throws IOException {

		ActualReportsApi api = new ActualReportsApi();
		api.setApiKey("61e5f04ca1794253ed17e6bb986c1702");
		api.setPrivateKey("68db1902ad1bb26d34b3f597488b9b28");
		api.setEmail("demo@actualreports.com");

		// Get editor url
		String eUrl = api.getEditorUrl();

		// Make simple request
		Map<String, Object> pd = api.request("template");

		// Make parameters for request
		Map params = new HashMap<>();
		params.put("format", "pdf");
		params.put("data", "[{\"grossWeight\": 40}]");

		// Simple pdf request
		Map<String, Object> p = api.request("template/2598/output", params,
				"POST");

		// Get resposne code
		int responseCode = (int) p.get("responseCode");
		// Get headers
		Map headermap = (Map) p.get("header");
		// Get content
		ArrayList content = (ArrayList) p.get("content");

		// Example of saving pdf file
		if (headermap.get("Content-Type").toString()
				.equals("[application/pdf]")) {
			DataOutputStream dos = new DataOutputStream(new FileOutputStream(
					"file.pdf"));
			Iterator iterator = content.iterator();
			while (iterator.hasNext()) {
				dos.writeByte((int) iterator.next());
			}
		}
		// Plain text
		else {
			Iterator iterator = content.iterator();
			while (iterator.hasNext()) {
				System.out.print((char) ((int) iterator.next()));
			}
		}
	}
}
