import os
import sys
import unittest
import io
from pathlib import Path
from urllib.error import HTTPError, URLError
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
import packagist


class PackagistPublicationTest(unittest.TestCase):
    def setUp(self):
        self.environment = patch.dict(os.environ, {
            'GITHUB_REPOSITORY': 'noldor/phpstan-extension',
            'GITHUB_SHA': 'a' * 40,
            'RELEASE_TAG': 'v0.0.1',
        }, clear=True)
        self.environment.start()
        self.addCleanup(self.environment.stop)

    def metadata(self, reference='a' * 40):
        return {'packages': {'noldors/phpstan-extension': [{
            'version': 'v0.0.1', 'source': {'reference': reference},
        }]}}

    @patch.object(packagist, 'request_json')
    def testVerifiesPublishedCommitWithoutCredentialsOrRefresh(self, request):
        request.return_value = self.metadata()
        packagist.verify_release('noldors/phpstan-extension')
        request.assert_called_once_with(
            'https://repo.packagist.org/p2/noldors/phpstan-extension.json',
            headers={
                'User-Agent': 'noldor/phpstan-extension release automation',
                'Cache-Control': 'no-cache',
            },
        )

    @patch.object(packagist.time, 'sleep')
    @patch.object(packagist, 'request_json')
    def testWaitsForTheQueuedUpdate(self, request, sleep):
        request.side_effect = [{'packages': {}}, self.metadata()]
        packagist.verify_release('noldors/phpstan-extension')
        sleep.assert_called_once_with(5)

    @patch.object(packagist.time, 'sleep')
    @patch.object(packagist, 'request_json')
    def testWaitsForInitialMetadataToBecomeAvailable(self, request, sleep):
        request.side_effect = [
            HTTPError('https://repo.packagist.org', 404, 'Not found', None, None),
            self.metadata(),
        ]
        packagist.verify_release('noldors/phpstan-extension')
        sleep.assert_called_once_with(5)

    @patch.object(packagist.time, 'sleep')
    @patch.object(packagist, 'request_json')
    def testRetriesATransientNetworkFailure(self, request, sleep):
        request.side_effect = [URLError('Temporary connection failure'), self.metadata()]
        packagist.verify_release('noldors/phpstan-extension')
        sleep.assert_called_once_with(5)

    @patch.object(packagist, 'request_json')
    def testPropagatesHttpErrorsOtherThanNotFound(self, request):
        request.side_effect = HTTPError('https://repo.packagist.org', 403, 'Forbidden', None, None)
        with self.assertRaises(HTTPError):
            packagist.verify_release('noldors/phpstan-extension')
        self.assertEqual(request.call_count, 1)

    @patch.object(packagist, 'request_json')
    def testRejectsAVersionPointingAtAnotherCommit(self, request):
        request.return_value = self.metadata('b' * 40)
        with self.assertRaisesRegex(RuntimeError, 'another commit'):
            packagist.verify_release('noldors/phpstan-extension')

    @patch.object(packagist.time, 'sleep')
    @patch.object(packagist, 'request_json')
    def testFailsWhenTheVersionNeverAppears(self, request, sleep):
        request.return_value = {'packages': {}}
        with self.assertRaisesRegex(RuntimeError, 'not visible.*webhook'):
            packagist.verify_release('noldors/phpstan-extension')
        self.assertEqual(request.call_count, 120)
        self.assertEqual(sleep.call_count, 119)
        sleep.assert_called_with(5)


class PackagistRequestTest(unittest.TestCase):
    @patch.object(packagist, 'urlopen')
    def testReadsPublicMetadataWithoutCredentialsOrRequestBody(self, open_url):
        open_url.return_value.__enter__.return_value = io.BytesIO(b'{"packages":{}}')
        result = packagist.request_json(
            'https://repo.packagist.org/p2/noldors/phpstan-extension.json',
            headers={'User-Agent': 'noldor/phpstan-extension release automation'},
        )
        request = open_url.call_args.args[0]
        self.assertEqual(request.full_url, 'https://repo.packagist.org/p2/noldors/phpstan-extension.json')
        self.assertEqual(request.get_method(), 'GET')
        self.assertIsNone(request.get_header('Authorization'))
        self.assertIsNone(request.data)
        self.assertEqual(result, {'packages': {}})


if __name__ == '__main__':
    unittest.main()
